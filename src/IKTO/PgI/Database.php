<?php

namespace IKTO\PgI;

use IKTO\PgI;

class Database
{
    protected $connection;
    protected $preparedStatements = array();
    protected $savepointNames = array();
    protected $transactionStack = array();

    public function __construct($dsn, $user = null, $password = null)
    {
        if ($user !== null) {
            $dsn .= " user=$user";
        }
        if ($password !== null) {
            $dsn .= " password=$password";
        }

        $this->connection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);

        if ($this->connection === false) {
            throw new RuntimeException(sprintf("Cannot connect to PostgreSQL database"));
        }
    }

    public function __destruct()
    {
        pg_close($this->connection);
    }

    public function getConnectionHandle()
    {
        return $this->connection;
    }

    public function addPreparedStatement(PreparedStatement $statement)
    {
        $this->preparedStatements[$statement->getName()] = 1;
    }

    public function removePreparedStatement(PreparedStatement $statement)
    {
        $name = $statement->getName();
        if (isset($this->preparedStatements[$name])) {
            unset($this->preparedStatements[$name]);
        }
    }

    public function isPreparedStatementExists(PreparedStatement $statement)
    {
        $name = $statement->getName();

        return isset($this->preparedStatements[$name]);
    }

    public function prepare($query)
    {
        return new PreparedStatement($this, $query);
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
                unset($this->savepointNames[$name]);
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

    public function pgPrepareStatement($name, $query)
    {
        return pg_prepare($this->connection, $name, $query);
    }

    public function pgExecutePreparedStatement($name, $args)
    {
        return pg_execute($this->connection, $name, $args);
    }

    public function pgQuery($query)
    {
        return pg_query($this->connection, $query);
    }

    public function pgQueryParams($query, $params)
    {
        return pg_query_params($this->connection, $query, $params);
    }

    protected function executeQuery($query, $types = array(), $params = array())
    {
        $typesIndex = array_keys($types);
        sort($typesIndex, SORT_NUMERIC);
        $paramTypes = array();
        foreach ($typesIndex as $typeIndex) {
            $paramTypes[$typeIndex - 1] = $types[$typeIndex];
        }

        return $this->pgQueryParams(
            $query,
            ParamsEncoder::encodeRow(
                $paramTypes,
                $params,
                $this->connection
            )
        );
    }

    protected function getSavepointName()
    {
        do {
            $name = uniqid() . uniqid();
        } while (isset($this->savepointNames[$name]));

        return $name;
    }
}
