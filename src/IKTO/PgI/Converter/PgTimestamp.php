<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Exception\InvalidArgumentException;

class PgTimestamp implements ConverterInterface
{
    public function encode($value, $type = null)
    {
        $this->assertValidValue($value);

        return $value->format('Y-m-d\TH:i:s');
    }

    public function decode($value, $type = null)
    {
        return new \DateTime($value);
    }

    /**
     * Checks value validness
     *
     * @param mixed $value
     */
    protected function assertValidValue($value)
    {
        /* Also checks DateTime for PHP < 5.5.0 */
        if (!($value instanceof \DateTime) && !($value instanceof \DateTimeInterface)) {
            throw new InvalidArgumentException("The timestamp must be passed as instance of DateTime or DateTimeInterface");
        }
    }
}
