<?php

namespace IKTO\PgI\Converter;

class PgJson implements ConverterInterface
{
    public function encode($value, $type = null)
    {
        return json_encode($value);
    }

    public function decode($value, $type = null)
    {
        // TODO: Decode JSON as object
        return json_decode($value, true);
    }
}
