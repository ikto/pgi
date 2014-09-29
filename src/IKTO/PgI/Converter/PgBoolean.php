<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgBoolean implements ConverterInterface, EncoderGuesserInterface
{
    public function encode($value, $type = null)
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException('The boolean must be passed as php boolean');
        }

        return $value ? 't' : 'f';
    }

    public function decode($value, $type = null)
    {
        if ($value == 't') {
            return true;
        } elseif ($value == 'f') {
            return false;
        } elseif ($value == null) {
            return null;
        }

        throw new InvalidArgumentException(sprintf('Invalid boolean data: %s', $value));
    }

    public function canEncode($value)
    {
        return is_bool($value);
    }
}
