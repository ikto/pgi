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
        /* Skip conversion if given value is [PHP] null */
        if (null === $value) {
            return null;
        }

        /* Try to guess value type */
        if (null === $type) {
            $type = $this->db->guessTypeByValue($value);
        }

        /* Encode array as postgres array */
        if ('_' == substr($type, 0, 1)) {
            $converter = $this->db->getConverterForType(DefaultTypes::ARRAY_OF);

            return $converter->encode($value, substr($type, 1));
        }

        /* Encode regular type */
        $converter = $this->db->getConverterForType($type);

        return $converter->encode($value);
    }
}
