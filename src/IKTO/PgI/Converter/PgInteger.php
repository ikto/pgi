<?php

namespace IKTO\PgI\Converter;

class PgInteger implements ConverterInterface
{
    public function encode($value, $type = null)
    {
        return $value;
    }

    public function decode($value, $type = null)
    {
        return intval($value);
    }
}
