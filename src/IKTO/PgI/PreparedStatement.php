<?php

namespace IKTO\PgI;

class PreparedStatement
{
    protected $hive;
    protected $result;
    protected $affectedRows;
    protected $name;

    protected $params = array();
    protected $paramTypes = array();

    public function __construct(Database $hive, $query)
    {
        $this->hive = $hive;

        do {
            $this->name = uniqid() . uniqid();
        } while ($this->hive->isPreparedStatementExists($this));

        if (pg_prepare($this->hive->getConnectionHandle(), $this->name, $query) === FALSE) {
            throw new RuntimeException('Cannot create prepared statement');
        }

        $this->hive->addPreparedStatement($this);
    }

    public function __destruct()
    {
        if ($this->result) {
            pg_free_result($this->result);
        }
        pg_query($this->hive->getConnectionHandle(), "DEALLOCATE PREPARE \"{$this->name}\"");
        $this->hive->removePreparedStatement($this);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    public function bindParam($n, $value, $type = null)
    {
        $this->params[$n - 1] = $value;
        if ($type !== null) {
            $this->paramTypes[$n - 1] = $type;
        }
    }

    public function execute($params = array())
    {
        $i = 0;
        foreach ($params as $param) {
            while (isset($this->params[$i])) {
                $i++;
            }
            $this->params[$i] = $param;
        }

        $this->result = pg_execute(
            $this->hive->getConnectionHandle(),
            $this->name,
            ParamsEncoder::encodeRow(
                $this->paramTypes,
                $this->params,
                $this->hive->getConnectionHandle()
            )
        );

        $this->paramTypes = array();
        $this->params = array();

        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);

            return true;
        }

        return false;
    }

    public function fetchRowArray()
    {
        if (!$this->result) {
            throw new RuntimeException("No result got from database");
        }

        $row = pg_fetch_row($this->result);

        if ($row) {
            $row = ResultDecoder::decodeRow($this->result, $row);
        }

        return $row;
    }

    public function fetchRowAssoc()
    {
        $row = $this->fetchRowArray();

        if (!$row) {
            return false;
        }

        $assoc = array();

        for ($i = 0; $i < count($row); $i++) {
            $assoc[pg_field_name($this->result, $i)] = $row[$i];
        }

        return $assoc;
    }
}
