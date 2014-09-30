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
        if ((substr($value, 0, 1) != '{') || (substr($value, -1) != '}')) {
            throw new InvalidArgumentException(sprintf('Invalid array data: %s', $value));
        }

        // Removes heading '{' and tailing '}'
        $value = substr($value, 1, -1);

        // Calculate array nesting level
        $nestingLevel = 0;
        while (substr($value, $nestingLevel, 1) == '{') {
            $nestingLevel++;
        }

        // Process nested array
        if ($nestingLevel > 0) {
            $t = substr($value, $nestingLevel, -$nestingLevel);
            $t = explode(str_repeat('}', $nestingLevel) . ',' . str_repeat('{', $nestingLevel), $t);
            $t = array_map(function ($e) use ($nestingLevel) {
                return str_repeat('{', $nestingLevel) . $e . str_repeat('}', $nestingLevel);
            }, $t);
            foreach ($t as $key => $value) {
                $t[$key] = $this->decode($value, $type);
            }

            return $t;
        }

        $value = str_getcsv($value);

        $converter = $this->db->getConverterForType($type);

        return array_map(function ($e) use ($converter) {
            return ($e === 'NULL') ? null : $converter->decode($e);
        }, $value);
    }

    public function canEncode($value)
    {
        return is_array($value);
    }
}
