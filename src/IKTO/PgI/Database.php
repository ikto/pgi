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

    public function doQuery($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            return pg_affected_rows($res);
        } else {
            return null;
        }
    }

    public function selectRowArray($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            $row = pg_fetch_row($res);

            if ($row) {
                $row = ResultDecoder::decodeRow($res, $row);
            }

            pg_free_result($res);

            return $row;
        } else {
            return null;
        }
    }

    public function selectRowAssoc($query, $types = array(), $params = array())
    {
        $res = $this->executeQuery($query, $types, $params);
        if ($res) {
            $row = pg_fetch_row($res);

            if ($row) {
                $row = ResultDecoder::decodeRow($res, $row);
            }

            $assoc = array();

            for ($i = 0; $i < count($row); $i++) {
                $assoc[pg_field_name($res, $i)] = $row[$i];
            }

            pg_free_result($res);

            return $assoc;
        } else {
            return null;
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
            }

            pg_free_result($res);

            return $rows;
        } else {
            return null;
        }
    }

    protected function executeQuery($query, $types = array(), $params = array())
    {
        $typesIndex = array_keys($types);
        sort($typesIndex, SORT_NUMERIC);
        $paramTypes = array();
        foreach ($typesIndex as $typeIndex) {
            $paramTypes[$typeIndex - 1] = $types[$typeIndex];
        }

        return pg_query_params(
            $this->connection,
            $query,
            ParamsEncoder::encodeRow(
                $paramTypes,
                $params,
                $this->connection
            )
        );
    }
}
