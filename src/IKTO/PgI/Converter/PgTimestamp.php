<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgTimestamp implements ConverterInterface
{
    public function encode($value, $type = null)
    {
        if (!($value instanceof \DateTimeInterface)) {
            throw new InvalidArgumentException("The timestamp must be passed as instance of DateTimeInterface");
        }

        return $value->format('Y-m-d\TH:i:s');
    }

    public function decode($value, $type = null)
    {
        return new \DateTime($value);
    }
}
