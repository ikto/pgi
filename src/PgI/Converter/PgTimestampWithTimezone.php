<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgTimestampWithTimezone extends PgTimestamp implements EncoderGuesserInterface
{
    public function encode($value, $type = null)
    {
        $this->assertValidValue($value);

        return $value->format('Y-m-d\TH:i:sP');
    }

    public function canEncode($value)
    {
        try {
            $this->assertValidValue($value);
        } catch (InvalidArgumentException $ex) {
            return false;
        }

        return true;
    }
}
