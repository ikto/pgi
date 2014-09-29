<?php

namespace IKTO\PgI\Helper;

use IKTO\PgI\Database\DatabaseAwareInterface;
use IKTO\PgI\Database\DatabaseInterface;

class ParamEncoder implements DatabaseAwareInterface
{
    /* @var DatabaseInterface */
    private $db;

    public function setDatabase(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Encodes one value for passing to pg_query_params
     *
     * @param mixed $value
     * @param null|string $type
     * @return mixed
     */
    public function encode($value, $type = null)
    {
        $converter = $this->db->getConverterForType($type);

        return $converter->encode($value);
    }
}
