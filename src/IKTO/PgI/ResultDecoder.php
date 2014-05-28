<?php

namespace IKTO\PgI;

class ResultDecoder
{
    public static function decodeRow($result, $row)
    {
        for ($i = 0; $i < count($row); $i++) {
            $type = pg_field_type($result, $i);

            switch ($type) {
                case 'bytea':
                    $row[$i] = pg_unescape_bytea($row[$i]);
                    break;
                case 'bool':
                    if ($row[$i] == 't') {
                        $row[$i] = true;
                    } else {
                        $row[$i] = false;
                    }
                    break;
                case 'int2':
                case 'int4':
                case 'int8':
                    $row[$i] = intval($row[$i]);
                    break;
                case 'numeric':
                case 'float4':
                case 'float8':
                    $row[$i] = floatval($row[$i]);
                    break;
                case 'json':
                    $row[$i] = json_decode($row[$i], true);
                    break;
                case 'timestamp':
                case 'timestamptz':
                    $row[$i] = new \DateTime($row[$i]);
                    break;
                case '_bytea':
                    $row[$i] = array_map(function ($val) {
                        return pg_unescape_bytea($val);
                    }, self::pgArrayToPhp($row[$i]));
                    break;
                case '_bool':
                    $row[$i] = array_map(function ($val) {
                        if ($val == 't') { return true; } else { return false; }
                    }, self::pgArrayToPhp($row[$i]));
                    break;
                case '_int2':
                case '_int4':
                case '_int8':
                    $row[$i] = array_map(function ($val) {
                        return intval($val);
                    }, self::pgArrayToPhp($row[$i]));
                    break;
                case '_numeric':
                case '_float4':
                case '_float8':
                    $row[$i] = array_map(function ($val) {
                        return floatval($val);
                    }, self::pgArrayToPhp($row[$i]));
                    break;
                case '_timestamp':
                case '_timestamptz':
                    $row[$i] = array_map(function ($val) {
                        return new \DateTime($val);
                    }, self::pgArrayToPhp($row[$i]));
                    break;
                case '_date':
                case '_time':
                case '_timetz':
                case '_varchar':
                case '_bpchar':
                case '_inet':
                case '_macaddr':
                case '_money':
                    $row[$i] = self::pgArrayToPhp($row[$i]);
                    break;
            }
        }

        return $row;
    }

    public static function pgArrayToPhp($text)
    {
        if (is_null($text)) {
            return array();
        } else if (is_string($text) && $text != '{}') {
            $text = substr($text, 1, -1); // Removes starting "{" and ending "}"
            if (substr($text, 0, 1) == '"') {
                $text = substr($text, 1);
            }
            if (substr($text, -1, 1) == '"') {
                $text = substr($text, 0, -1);
            }
            // If double quotes are present, we know we're working with a string.
            if (strstr($text, '"')) { // Assuming string array.
                $values = explode('","', $text);
            } else { // Assuming Integer array.
                $values = explode(',', $text);
            }
            $fixed_values = array();
            foreach ($values as $value) {
                $value = str_replace('\\"', '"', $value);
                $fixed_values[] = $value;
            }
            return $fixed_values;
        } else {
            return array();
        }
    }
}
