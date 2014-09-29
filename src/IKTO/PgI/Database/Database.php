<?php

namespace IKTO\PgI\Database;

use IKTO\PgI;
use IKTO\PgI\Exception\ConnectionException;
use IKTO\PgI\Exception\DuplicationException;
use IKTO\PgI\Exception\InvalidArgumentException;
use IKTO\PgI\Statement\Prepared;
use IKTO\PgI\Statement\Plain;
use IKTO\PgI\ResultDecoder;
use IKTO\PgI\Helper\PgExceptionHelper;
use IKTO\PgI\Helper\ParamEncoder;
use IKTO\PgI\Helper\ParamDecoder;
use IKTO\PgI\Converter\ConverterInterface;

class Database implements DatabaseInterface
{
    protected $connection;
    protected $preparedStatements = array();
    protected $savepointNames = array();
    protected $transactionStack = array();
    protected $converters = array();

    /* @var ParamEncoder */
    private $encoder;

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
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            return pg_affected_rows($res);
        } else {
            return false;
        }
    }

    public function selectRowArray($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            $row = pg_fetch_row($res);

            if ($row) {
                $row = ResultDecoder::decodeRow($res, $row);
                pg_free_result($res);

                return $row;
            } else {
                pg_free_result($res);

                return null;
            }
        } else {
            return false;
        }
    }

    public function selectRowAssoc($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            $row = pg_fetch_row($res);

            if ($row) {
                $row = ResultDecoder::decodeRow($res, $row);
                $assoc = array();
                for ($i = 0; $i < count($row); $i++) {
                    $assoc[pg_field_name($res, $i)] = $row[$i];
                }
                pg_free_result($res);

                return $assoc;
            } else {
                pg_free_result($res);

                return null;
            }
        } else {
            return false;
        }
    }

    public function selectColArray($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            $rows = pg_fetch_all_columns($res, 0);

            if (is_array($rows)) {
                $rows = array_map(function ($value) use ($res) {
                    $r = ResultDecoder::decodeRow($res, array($value));
                    return $r[0];
                }, $rows);
                pg_free_result($res);

                return $rows;
            } else {
                pg_free_result($res);

                return null;
            }
        } else {
            return false;
        }
    }

    public function beginWork()
    {
        $status = pg_transaction_status($this->connection);
        if (($status == PGSQL_TRANSACTION_INTRANS) || ($status == PGSQL_TRANSACTION_INERROR)) {
            $name = $this->getSavepointName();
            if ($this->pgQuery('SAVEPOINT "' . $name . '"')) {
                array_push($this->transactionStack, $name);
                $this->savepointNames[$name] = 1;
            } else {
                throw new \RuntimeException("Cannot create savepoint $name");
            }
        } else {
            if (!$this->pgQuery('BEGIN')) {
                throw new \RuntimeException("Cannot start the transaction");
            }
        }

    }

    public function rollback()
    {
        if (count($this->transactionStack)) {
            $name = array_pop($this->transactionStack);
            if ($this->pgQuery('ROLLBACK TO "' . $name . '"')) {
                if ($this->pgQuery('RELEASE SAVEPOINT "' . $name . '"')) {
                    unset($this->savepointNames[$name]);
                }
            } else {
                throw new \RuntimeException("Cannot rollback to savepoint $name");
            }
        } else {
            if (!$this->pgQuery('ROLLBACK')) {
                throw new \RuntimeException('Cannot cancel transaction');
            }
        }
    }

    public function commit()
    {
        if (count($this->transactionStack)) {
            $name = array_pop($this->transactionStack);
            if ($this->pgQuery('RELEASE SAVEPOINT "' . $name . '"')) {
                unset($this->savepointNames[$name]);
            } else {
                throw new \RuntimeException("Cannot release savepoint $name");
            }
        } else {
            if (!$this->pgQuery('COMMIT')) {
                throw new \RuntimeException('Cannot commit transaction');
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
        $res = $this->executeQuery('SELECT NEXTVAL(\'' . $sequence . '\'::regclass)');
        if (!$res) {
            return false;
        }

        $row = pg_fetch_row($res);
        if (!$row) {
            pg_free_result($res);

            return false;
        }

        $row = ResultDecoder::decodeRow($res, $row);
        pg_free_result($res);

        return $row[0];
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

        if (!($this->converters[$type] instanceof InvalidArgumentException)) {
            $this->converters[$type] = $this->createConverter($this->converters[$type]);
        }

        return $this->converters[$type];
    }

    protected function executeQuery($query, $types = array(), $params = array())
    {
        $typesIndex = array_keys($types);
        sort($typesIndex, SORT_NUMERIC);
        $paramTypes = array();
        foreach ($typesIndex as $typeIndex) {
            $paramTypes[$typeIndex - 1] = $types[$typeIndex];
        }

//        return $this->pgQueryParams(
//            $query,
//            ParamsEncoder::encodeRow(
//                $paramTypes,
//                $params,
//                $this->connection
//            )
//        );
        return false;
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

    protected function injectDependencies($object)
    {
        if ($object instanceof PgI\PgConnectionAwareInterface) {
            $object->setPgConnection($this->connection);
        }

        if ($object instanceof DatabaseAwareInterface) {
            $object->setDatabase($this);
        }
    }
}
