<?php

namespace IKTO\PgI;

class ParamsEncoder
{
    const BYTEA         = 'bytea';

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
