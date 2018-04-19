<?php

namespace IKTO\PgI\Statement;

use IKTO\PgI\Database\DatabaseInterface;

interface StatementInterface
{
    /**
     * Creates new query object
     *
     * @param DatabaseInterface $db The database interface
     * @param string $query The query (SQL)
     */
    public function __construct(DatabaseInterface $db, $query);

    /**
     * Binds query parameter to certain position (value binding)
     *
     * @param integer $n The param position (begin from 1)
     * @param mixed $value The param value
     * @param null|string $type The param type
     */
    public function bindValue($n, $value, $type = null);

    /**
     * Sets data type in column in result
     *
     * @param string|integer $field The field number (begin from 1) or column name
     * @param string $type
     */
    public function setResultType($field, $type);

    /**
     * Executes query
     *
     * @param array $params The parameters (begin from 0)
     * @return bool True if query executed successfully
     */
    public function execute(array $params = []);

    /**
     * Fetches one row from query result as numeric array
     *
     * @param array $types The result column types
     * @return array
     */
    public function fetchRowArray(array $types = []);

    /**
     * Fetches one row from query result as associative array
     *
     * @param array $types The result column types
     * @return array
     */
    public function fetchRowAssoc(array $types = []);

    /**
     * Gets all of certain column values
     *
     * @param integer|string $column The column number (begin from 1) or column name
     * @param string|null $type The column type
     * @return array
     */
    public function getColumnValues($column, $type = null);

    /**
     * Gets row number after query execution
     *
     * @return integer
     */
    public function getAffectedRows();

    /**
     * Moves result cursor to specified position
     *
     * @param integer $n The new position of result cursor
     */
    public function seek($n);
}
