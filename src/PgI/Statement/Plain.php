<?php

namespace IKTO\PgI\Statement;

use IKTO\PgI\Database\DatabaseInterface;
use IKTO\PgI\Exception\InvalidArgumentException;
use IKTO\PgI\Exception\MissingConverterException;

class Plain implements StatementInterface
{
    /* @var DatabaseInterface */
    protected $db;

    /* @var string */
    protected $query;

    /* @var array */
    protected $params = [];

    /* @var array */
    protected $paramTypes = [];

    /* @var array */
    protected $resultTypes = [];

    /* @var resource */
    protected $result = null;

    /* @var integer */
    protected $affectedRows = null;

    public function __construct(DatabaseInterface $db, $query)
    {
        $this->db = $db;
        $this->query = $query;
    }

    public function bindValue($n, $value, $type = null)
    {
        $this->params[$n-1] = $value;
        if (null !== $type) {
            $this->paramTypes[$n-1] = $type;
        }
    }

    public function setResultType($field, $type)
    {
        if (is_int($field)) {
            $field--;
        }

        $this->resultTypes[$field] = $type;
    }

    public function execute(array $params = [])
    {
        $this->result = $this->db->pgQueryParams($this->query, $this->getParams($params));

        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);

            return true;
        }

        return false;
    }

    public function fetchRowArray(array $types = [])
    {
        $this->assertResultExists();

        $row = $this->fetchRow($this->result, $types);

        return $row;
    }

    public function fetchRowAssoc(array $types = [])
    {
        $row = $this->fetchRowArray($types);

        if (!$row) {
            return false;
        }

        $assoc = [];

        for ($i = 0, $j = count($row); $i < $j; $i++) {
            $assoc[pg_field_name($this->result, $i)] = $row[$i];
        }
        unset($row, $i, $j);

        return $assoc;
    }

    public function getColumnValues($column, $type = null)
    {
        $this->assertResultExists();

        // Determine column number by the int number or string name
        if (is_int($column)) {
            $columnNumber = $column - 1;
        } else {
            $columnNumber = pg_field_num($this->result, $column);
            if ($columnNumber < 0) {
                throw new InvalidArgumentException(sprintf('Cannot resolve column with name %s', $column));
            }
        }

        // Get column data
        $rows = pg_fetch_all_columns($this->result, $columnNumber);

        $types = array_merge($this->resultTypes, [$columnNumber => $type]);

        // Try user-defined result type first
        $auto = false;
        $type = $types[$columnNumber] ?? null;
        if (null === $type) {
            $name = pg_field_name($this->result, $columnNumber);
            $type = $types[$name] ?? null;
        }

        // And then try to get type from result
        if (!$type) {
            $type = pg_field_type($this->result, $columnNumber);
            $auto = true;
        }

        foreach ($rows as $key => $value) {
            try {
                $rows[$key] = $this->db->decoder()->decode($value, $type);
            } catch (MissingConverterException $ex) {
                if (!$auto) {
                    throw $ex;
                }
            }
        }

        return $rows;
    }

    public function getAffectedRows()
    {
        $this->assertResultExists();

        return $this->affectedRows;
    }

    public function seek($n)
    {
        $this->assertResultExists();
        pg_result_seek($this->result, $n);
    }

    protected function assertResultExists()
    {
        if (!$this->result) {
            throw new InvalidArgumentException('Result not exists. Perhaps query has not been executed.');
        }
    }

    protected function getParams($userDefinedParams = [])
    {
        $params = $this->params;

        $i = 0;
        foreach ($userDefinedParams as $param) {
            while (isset($params[$i])) {
                $i++;
            }
            $params[$i] = $param;
        }

        // Encoding input arguments
        foreach ($params as $key => $value) {
            try {
                $params[$key] = $this
                    ->db
                    ->encoder()
                    ->encode($value, $this->paramTypes[$key] ?? null);
            } catch (MissingConverterException $ex) {
                if (isset($this->paramTypes[$key])) {
                    throw $ex;
                }
            }
        }

        return $params;
    }

    protected function fetchRow($result, $userDefinedResultTypes = [])
    {
        $row = pg_fetch_row($result);

        if (!$row) {
            return false;
        }

        $types = array_merge($this->resultTypes, $userDefinedResultTypes);

        foreach ($row as $key => $value) {
            // Try user-defined result type first
            $type = $types[$key] ?? null;
            if (null === $type) {
                $name = pg_field_name($result, $key);
                $type = $types[$name] ?? null;
            }

            if ($type) {
                $row[$key] = $this->db->decoder()->decode($value, $type);
                continue;
            }

            // Skip conversion if converter has not registered
            try {
                $type = pg_field_type($result, $key);
                $row[$key] = $this->db->decoder()->decode($value, $type);
            } catch (MissingConverterException $ex) {
                // If $value was autodetected array
                if ('_' == substr($type, 0, 1)) {
                    try {
                        $row[$key] = $this->db->decoder()->decode($value, '_');
                    } catch (MissingConverterException $ex) {
                        /* DO NOTHING */
                    }
                }
            }
        }

        return $row;
    }
}
