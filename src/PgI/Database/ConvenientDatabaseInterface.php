<?php

namespace IKTO\PgI\Database;

interface ConvenientDatabaseInterface
{
    /**
     * Gets next sequence value
     *
     * @param string $sequence
     * @return integer
     */
    public function getSeqNextValue($sequence);

    /**
     * Gets current sequence value
     *
     * @param string $sequence
     * @return integer
     */
    public function getSeqCurrentValue($sequence);

    /**
     * Selects one row from database (as numeric array)
     *
     * @param string $query SQL query
     * @param array $types The parameter types
     * @param array $params The parameters
     * @return array|null
     */
    public function selectRowArray($query, $types = array(), $params = array());

    /**
     * Selects one row from database (as associative array)
     *
     * @param string $query SQL query
     * @param array $types The parameter types
     * @param array $params The parameters
     * @return array|null
     */
    public function selectRowAssoc($query, $types = array(), $params = array());

    /**
     * Selects one column from all rows
     *
     * @param string $query SQL query
     * @param array $types The parameter array
     * @param array $params The parameters
     * @return array|null
     */
    public function selectColArray($query, $types = array(), $params = array());

    /**
     * Executes query with specified parameters
     *
     * @param string $query SQL query
     * @param array $types The parameter array
     * @param array $params The parameter
     * @return integer
     */
    public function doQuery($query, $types = array(), $params = array());
}
