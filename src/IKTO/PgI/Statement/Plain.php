<?php

namespace IKTO\PgI\Statement;

use IKTO\PgI\Database\DatabaseInterface;
use IKTO\PgI\Exception\InvalidArgumentException;

class Plain implements StatementInterface
{
    /* @var DatabaseInterface */
    protected $db;

    /* @var string */
    protected $query;

    /* @var array */
    protected $params = array();

    /* @var array */
    protected $paramTypes = array();

    /* @var resource */
    protected $result = null;

    /* @var integer */
    protected $affectedRows = null;

    public function __construct(DatabaseInterface $db, $query)
    {
        $this->db = $db;
        $this->query = $query;
    }

    public function bindParam($n, $value, $type = null)
    {
        $this->params[$n-1] = $value;
        if (null !== $type) {
            $this->paramTypes[$n-1] = $type;
        }
    }

    public function execute(array $params = array())
    {
        $this->result = $this->db->pgQueryParams($this->query, $this->getParams($params));

        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);

            return true;
        }

        return false;
    }

    public function fetchRowArray()
    {
        $this->assertResultExists();

        $row = pg_fetch_row($this->result);

        // TODO: Decode results

        return $row;
    }

    public function fetchRowAssoc()
    {
        $row = $this->fetchRowArray();

        if (!$row) {
            return false;
        }

        $assoc = array();

        for ($i = 0, $j = count($row); $i < $j; $i++) {
            $assoc[pg_field_name($this->result, $i)] = $row[$i];
        }

        return $assoc;
    }

    protected function assertResultExists()
    {
        if (!$this->result) {
            throw new InvalidArgumentException('Result not exists. Perhaps query has not been executed.');
        }
    }

    protected function getParams($userDefinedParams = array())
    {
        $params = $this->params;

        $i = 0;
        foreach ($userDefinedParams as $param) {
            while (isset($params[$i])) { $i++; }
            $params[$i] = $param;
        }

        // Encoding input arguments
        foreach ($params as $key => $value) {
            $params[$key] = $this
                ->db
                ->encoder()
                ->encode($value, isset($this->paramTypes[$key]) ? $this->paramTypes[$key] : null);
        }

        return $params;
    }
}
