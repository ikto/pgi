<?php

namespace IKTO\PgI;

class Database
{
    protected $connection;
    protected $preparedStatements = array();

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

    public function doQuery($query)
    {
        $res = $this->executeQuery($query);
        if ($res) {
            return pg_affected_rows($res);
        } else {
            return null;
        }
    }

    protected function executeQuery($query)
    {
        return pg_query($this->connection, $query);
    }
}
