<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Database\DatabaseAwareInterface;
use IKTO\PgI\Database\DatabaseInterface;
use IKTO\PgI\Exception\InvalidArgumentException;

class PgArray implements ConverterInterface, DatabaseAwareInterface, EncoderGuesserInterface
{
    /* @var DatabaseInterface */
    private $db;

    public function setDatabase(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function encode($value, $type = null)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('The array must be passed as php array');
        }

        $result = array();
        $converter = $this->db->getConverterForType($type);
        foreach ($value as $element) {
            if (is_array($element)) {
                $result[] = $this->encode($element);
            } else {
                $element = $converter->encode($element);
                $element = str_replace('"', '\\"', $element); // Escape double-quotes.
//                $element = pg_escape_string()
                $result[] = $element;
            }
        }

        return '{' . implode(',', $result) . '}'; // format
    }

    public function decode($value, $type = null)
    {
        // TODO: Implement decode() method.
    }

    public function canEncode($value)
    {
        return is_array($value);
    }
}
