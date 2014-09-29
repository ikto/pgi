<?php

namespace IKTO\PgI\Converter;

class PgFloat implements ConverterInterface
{
    public function encode($value, $type = null)
    {
        return $value;
    }

    public function decode($value, $type = null)
    {
        return floatval($value);
    }
}
