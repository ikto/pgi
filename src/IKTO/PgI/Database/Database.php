<?php

namespace IKTO\PgI\Database;

use IKTO\PgI;
use IKTO\PgI\Exception\ConnectionException;
use IKTO\PgI\Exception\DuplicationException;
use IKTO\PgI\Exception\InvalidArgumentException;
use IKTO\PgI\Exception\QueryException;
use IKTO\PgI\Exception\TransactionException;
use IKTO\PgI\Statement\Prepared;
use IKTO\PgI\Statement\Plain;
use IKTO\PgI\Helper\PgExceptionHelper;
use IKTO\PgI\Helper\ParamEncoder;
use IKTO\PgI\Helper\ParamDecoder;
use IKTO\PgI\Helper\DefaultTypes;
use IKTO\PgI\Converter\ConverterInterface;

class Database implements DatabaseInterface, ConvenientDatabaseInterface
{
    protected $connection;
    protected $preparedStatements = array();
    protected $savepointNames = array();
    protected $transactionStack = array();
    protected $converters = array();

    /* @var ParamEncoder */
    private $encoder;

    /* @var ParamDecoder */
    private $decoder;

    public function __construct($dsn, $user = null, $password = null)
    {
        if ($user !== null) {
            $dsn .= " user=$user";
        }
        if ($password !== null) {
            $dsn .= " password=$password";
        }

        $connectionError = null;
        set_error_handler(function ($errno, $errstr) use (&$connectionError) {
            $connectionError = $errstr;
        });
        $this->connection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        restore_error_handler();

        if (false === $this->connection) {
            throw new ConnectionException($connectionError ?: 'Unable to connect to PostgreSQL server');
        }

        $this->registerDefaultConverters();
    }

    public function __destruct()
    {
        foreach ($this->converters as $key => $value) {
            if ($value instanceof ConverterInterface) {
                unset($this->converters[$key]);
            }
        }

        if ($this->encoder) {
            unset($this->encoder);
        }

        if ($this->decoder) {
            unset($this->decoder);
        }

        @pg_close($this->connection);
    }

    public function getConnectionHandle()
    {
        return $this->connection;
    }

    public function prepare($query)
    {
        return new Prepared($this, $query);
    }

    public function create($query)
    {
        return new Plain($this, $query);
    }

    public function doQuery($query, $types = array(), $params = array())
    {
        $query = $this->create($query);
        foreach ($params as $key => $value) {
            $query->bindValue($key+1, $value, isset($types[$key]) ? $types[$key] : null);
        }
        $query->execute();

        return $query->getAffectedRows();
    }

    public function selectRowArray($query, $types = array(), $params = array())
    {
        $query = $this->create($query);
        foreach ($params as $key => $value) {
            $query->bindValue($key+1, $value, isset($types[$key]) ? $types[$key] : null);
        }
        $query->execute();

        return $query->fetchRowArray();
    }

    public function selectRowAssoc($query, $types = array(), $params = array())
    {
        $query = $this->create($query);
        foreach ($params as $key => $value) {
            $query->bindValue($key+1, $value, isset($types[$key]) ? $types[$key] : null);
        }
        $query->execute();

        return $query->fetchRowAssoc();
    }

    public function selectColArray($query, $types = array(), $params = array())
    {
        $query = $this->create($query);
        foreach ($params as $key => $value) {
            $query->bindValue($key+1, $value, isset($types[$key]) ? $types[$key] : null);
        }
        $query->execute();

        return $query->getColumnValues(1);
    }

    public function beginWork()
    {
        $status = pg_transaction_status($this->connection);

        switch ($status) {
            case PGSQL_TRANSACTION_INTRANS:
            case PGSQL_TRANSACTION_INERROR:
                $name = $this->getSavepointName();
                try {
                    $this->pgQuery('SAVEPOINT "' . $name . '"');
                }
                catch (QueryException $ex) {
                    throw new TransactionException(
                        sprintf('Cannot create savepoint %s', $name),
                        null, $ex
                    );
                }
                $this->savepointNames[$name] = 1;

                break;
            default:
                try {
                    $this->pgQuery('BEGIN');
                }
                catch (QueryException $ex) {
                    throw new TransactionException(
                        'Cannot start the transaction',
                        null, $ex
                    );
                }

                break;
        }
    }

    public function rollback()
    {
        if (count($this->transactionStack)) {
            $name = array_pop($this->transactionStack);
            try {
                $this->pgQuery('ROLLBACK TO "' . $name . '"');
                $this->pgQuery('RELEASE SAVEPOINT "' . $name . '"');
            }
            catch (QueryException $ex) {
                throw new TransactionException(
                    sprintf('Cannot rollback to savepoint %s', $name),
                    null, $ex
                );
            }

            unset($this->savepointNames[$name]);
        } else {
            try {
                $this->pgQuery('ROLLBACK');
            }
            catch (QueryException $ex) {
                throw new TransactionException(
                    'Cannot cancel transaction',
                    null, $ex
                );
            }
        }
    }

    public function commit()
    {
        if (count($this->transactionStack)) {
            $name = array_pop($this->transactionStack);
            try {
                $this->pgQuery('RELEASE SAVEPOINT "' . $name . '"');
            }
            catch (QueryException $ex) {
                throw new TransactionException(
                    sprintf('Cannot release savepoint %s', $name),
                    null, $ex
                );
            }

            unset($this->savepointNames[$name]);
        } else {
            try {
                $this->pgQuery('COMMIT');
            }
            catch (QueryException $ex) {
                throw new TransactionException(
                    'Cannot commit transaction',
                    null, $ex
                );
            }
        }
    }

    public function getTransactionStatus()
    {
        $status = pg_transaction_status($this->connection);
        switch ($status) {
            case PGSQL_TRANSACTION_IDLE:
                return PgI::TRANSACTION_INACTIVE;
            case PGSQL_TRANSACTION_INTRANS:
                return PgI::TRANSACTION_ACTIVE;
            case PGSQL_TRANSACTION_INERROR:
                return PgI::TRANSACTION_ERROR;
            default:
                return false;
        }
    }

    public function getSeqNextValue($sequence)
    {
        $res = $this->pgQueryParams('SELECT NEXTVAL($1::regclass)', array($sequence));

        $row = pg_fetch_row($res);
        if (!$row) {
            throw new \InvalidArgumentException('SELECT NEXTVAL has not returned row.');
        }

        pg_free_result($res);

        return intval($row[0]);
    }

    public function getSeqCurrentValue($sequence)
    {
        $res = $this->pgQueryParams('SELECT CURRVAL($1::regclass)', array($sequence));

        $row = pg_fetch_row($res);
        if (!$row) {
            throw new \InvalidArgumentException('SELECT CURRVAL has not returned row.');
        }

        pg_free_result($res);

        return intval($row[0]);
    }

    public function pgPrepare($name, $query)
    {
        if (isset($this->preparedStatements[$name])) {
            throw new DuplicationException(sprintf('Statement with name "%s" already prepared', $name));
        }

        if (PgExceptionHelper::provideQueryException(function ($connection, $name, $query) {
            return pg_prepare($connection, $name, $query);
        }, array($this->connection, $name, $query))) {
            $this->preparedStatements[$name] = 1;
        }
    }

    public function pgExecute($name, $args)
    {
        return PgExceptionHelper::provideQueryException(function ($connection, $name, $args) {
            return pg_execute($connection, $name, $args);
        }, array($this->connection, $name, $args));
    }

    public function pgDeallocate($name)
    {
        PgExceptionHelper::provideQueryException(function ($connection, $query) {
            return pg_query($connection, $query);
        }, array($this->connection, "DEALLOCATE PREPARE \"{$name}\""));
        unset($this->preparedStatements[$name]);
    }

    public function pgQuery($query)
    {
        return PgExceptionHelper::provideQueryException(function ($connection, $query) {
            return pg_query($connection, $query);
        }, array($this->connection, $query));
    }

    public function pgQueryParams($query, $params)
    {
        return PgExceptionHelper::provideQueryException(function ($connection, $query, $params) {
            return pg_query_params($connection, $query, $params);
        }, array($this->connection, $query, $params));
    }

    public function getPreparedStatementName($query)
    {
        return md5($query . uniqid());
    }

    public function encoder()
    {
        if (!$this->encoder) {
            $this->encoder = new ParamEncoder();
            $this->injectDependencies($this->encoder);
        }

        return $this->encoder;
    }

    public function decoder()
    {
        if (!$this->decoder) {
            $this->decoder = new ParamDecoder();
            $this->injectDependencies($this->decoder);
        }

        return $this->decoder;
    }

    public function getConverterForType($type)
    {
        if (!isset($this->converters[$type])) {
            throw new InvalidArgumentException(sprintf('Cannot find converter for type "%s"', $type));
        }

        if (!($this->converters[$type] instanceof ConverterInterface)) {
            $this->converters[$type] = $this->createConverter($this->converters[$type]);
        }

        return $this->converters[$type];
    }

    public function registerConverter($type, $converter)
    {
        if (isset($this->converters[$type])) {
            throw new DuplicationException(sprintf('Converter for type %s is already registered', $type));
        }

        $this->converters[$type] = $converter;
    }

    public function unregisterConverter($type)
    {
        unset($this->converters[$type]);
    }

    protected function getSavepointName()
    {
        do {
            $name = uniqid() . uniqid();
        } while (isset($this->savepointNames[$name]));

        return $name;
    }

    /**
     * Creates new instance of data converter by specification
     *
     * @param string|array $specification The converter specification (class name, etc)
     * @return ConverterInterface
     * @throws InvalidArgumentException
     */
    protected function createConverter($specification)
    {
        if (!is_array($specification)) {
            $specification = array($specification);
        }

        if (!isset($specification[0])) {
            throw new InvalidArgumentException('Cannot find converter class name in specification');
        }

        if (!class_exists($specification[0])) {
            throw new InvalidArgumentException(sprintf('Converter class %s is not found', $specification[0]));
        }

        $className = $specification[0];
        $object = new $className();

        if (!($object instanceof ConverterInterface)) {
            throw new InvalidArgumentException('Converter class must be an instance of ConverterInterface');
        }

        $this->injectDependencies($object);

        return $object;
    }

    /**
     * Injects necessary dependencies into given object
     *
     * @param object $object
     */
    protected function injectDependencies($object)
    {
        if ($object instanceof PgI\PgConnectionAwareInterface) {
            $object->setPgConnection($this->connection);
        }

        if ($object instanceof DatabaseAwareInterface) {
            $object->setDatabase($this);
        }
    }

    /**
     * Registers default set of converters
     */
    protected function registerDefaultConverters()
    {
        $this->registerConverter(DefaultTypes::ARRAY_OF, array('IKTO\\PgI\\Converter\\PgArray'));
        $this->registerConverter(DefaultTypes::BOOLEAN, array('IKTO\\PgI\\Converter\\PgBoolean'));
        $this->registerConverter(DefaultTypes::BYTEA, array('IKTO\\PgI\\Converter\\PgBytea'));
        $this->registerConverter(DefaultTypes::FLOAT, array('IKTO\\PgI\\Converter\\PgFloat'));
        $this->registerConverter(DefaultTypes::DOUBLE, array('IKTO\\PgI\\Converter\\PgFloat'));
        $this->registerConverter(DefaultTypes::SMALLINT, array('IKTO\\PgI\\Converter\\PgInteger'));
        $this->registerConverter(DefaultTypes::INTEGER, array('IKTO\\PgI\\Converter\\PgInteger'));
        $this->registerConverter(DefaultTypes::BIGINT, array('IKTO\\PgI\\Converter\\PgInteger'));
        $this->registerConverter(DefaultTypes::NUMERIC, array('IKTO\\PgI\\Converter\\PgFloat'));
        $this->registerConverter(DefaultTypes::JSON, array('IKTO\\PgI\\Converter\\PgJson'));
        $this->registerConverter(DefaultTypes::TIMESTAMP_WITHOUT_TIMEZONE, array('IKTO\\PgI\\Converter\\PgTimestamp'));
        $this->registerConverter(DefaultTypes::TIMESTAMP_WITH_TIMEZONE, array('IKTO\\PgI\\Converter\\PgTimestampWithTimezone'));
    }
}
