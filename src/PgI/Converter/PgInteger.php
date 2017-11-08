<?php

namespace IKTO\PgI\Converter;

class PgInteger implements ConverterInterface, EncoderGuesserInterface
{
    public function encode($value, $type = null)
    {
        return $value;
    }

    public function decode($value, $type = null)
    {
        return intval($value);
    }

    public function canEncode($value)
    {
        return is_int($value);
    }
}
