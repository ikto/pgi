<?php

namespace IKTO\PgI;

class ParamsEncoder
{
    const BYTEA         = 'bytea';
    const JSON          = 'json';
    const TIMESTAMP     = 'timestamp';
    const TIMESTAMPTZ   = 'timestamptz';

    public static function encodeRow($types = array(), $values = array(), $pgconn = null)
    {
        $keys = array_keys($values);
        sort($keys, SORT_NUMERIC);

        $output = array();

        foreach ($keys as $key) {
            $value = $values[$key];

            if (isset($types[$key])) {
                if ($types[$key] == self::JSON) {
                    $value = json_encode($value);
                } elseif ($types[$key] == self::BYTEA) {
                    if ($pgconn) {
                        $value = pg_escape_bytea($pgconn, $value);
                    } else {
                        $value = pg_escape_bytea($value);
                    }
                } elseif ($types[$key] == self::TIMESTAMP) {
                    if ($value instanceof \DateTimeInterface || $value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                        /* @var \DateTimeInterface $value */
                        $value = $value->format('Y-m-d\TH:i:s');
                    } else {
                        throw new InvalidArgumentException("The timestamp data must be represented as DateTime instance (or similar)");
                    }
                } elseif ($types[$key] == self::TIMESTAMPTZ) {
                    if ($value instanceof \DateTimeInterface || $value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                        /* @var \DateTimeInterface $value */
                        $value = $value->format(\DateTime::W3C);
                    } else {
                        throw new InvalidArgumentException("The timestamptz data must be represented as DateTime instance (or similar)");
                    }
                }
            }

            if (is_array($value)) {
                $value = self::pgArrayFromPhp($value);
            }
            elseif (is_bool($value)) {
                $value = $value ? 't' : 'f';
            }
            elseif ($value instanceof \DateTimeInterface || $value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
                /* @var \DateTimeInterface $value */
                $value = $value->format(\DateTime::W3C);
            }

            $output[] = $value;
        }

        return $output;
    }

    public static function pgArrayFromPhp($array)
    {
        $array = (array)$array; // Type cast to array.
        $result = array();
        foreach ($array as $entry) { // Iterate through array.
            if (is_array($entry)) { // Supports nested arrays.
                $result[] = self::pgArrayFromPhp($entry);
            } else {
                $entry = str_replace('"', '\\"', $entry); // Escape double-quotes.
                $entry = pg_escape_string($entry); // Escape everything else.
                $result[] = '"' . $entry . '"';
            }
        }
        return '{' . implode(',', $result) . '}'; // format
    }
}
