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
     * Binds query parameter to certain position
     *
     * @param integer $n The param position (begin from 1)
     * @param mixed $value The param value
     * @param null|string $type The param type
     */
    public function bindParam($n, $value, $type = null);

    /**
     * Executes query
     *
     * @param array $params The parameters (begin from 0)
     * @return bool True if query executed successfully
     */
    public function execute(array $params = array());

    /**
     * Fetches one row from query result as numeric array
     *
     * @return array
     */
    public function fetchRowArray();

    /**
     * Fetches one row from query result as associative array
     *
     * @return array
     */
    public function fetchRowAssoc();
}
