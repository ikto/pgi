<?php

namespace IKTO\PgI\Helper;

use IKTO\PgI\Database\DatabaseAwareInterface;
use IKTO\PgI\Database\DatabaseInterface;

class ParamDecoder implements DatabaseAwareInterface
{
    /* @var DatabaseInterface */
    private $db;

    public function setDatabase(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Decodes one value received from postgres driver
     *
     * @param string $value
     * @param null|string $type
     * @return mixed
     */
    public function decode($value, $type = null)
    {
        /* Skip conversion if given value is [SQL] null */
        if (null === $value) {
            return null;
        }

        /* Decode array as array of elements */
        if ('_' == $type[0]) {
            $converter = $this->db->getConverterForType(DefaultTypes::ARRAY_OF);

            return $converter->decode($value, substr($type, 1));
        }

        /* Decode regular type */
        $converter = $this->db->getConverterForType($type);

        return $converter->decode($value);
    }
}
