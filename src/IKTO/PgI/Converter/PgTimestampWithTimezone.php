<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgTimestampWithTimezone implements ConverterInterface, EncoderGuesserInterface
{
    public function encode($value, $type = null)
    {
        if (!($value instanceof \DateTimeInterface)) {
            throw new InvalidArgumentException("The timestamp must be passed as instance of DateTimeInterface");
        }

        return $value->format(\DateTime::W3C);
    }

    public function decode($value, $type = null)
    {
        return new \DateTime($value);
    }

    public function canEncode($value)
    {
        return ($value instanceof \DateTimeInterface);
    }
}
