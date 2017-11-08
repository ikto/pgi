<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgTimestampWithTimezone extends PgTimestamp implements EncoderGuesserInterface
{
    public function encode($value, $type = null)
    {
        $this->assertValidValue($value);

        return $value->format(\DateTime::W3C);
    }

    public function canEncode($value)
    {
        try {
            $this->assertValidValue($value);
        }
        catch (InvalidArgumentException $ex) {
            return false;
        }

        return true;
    }
}
