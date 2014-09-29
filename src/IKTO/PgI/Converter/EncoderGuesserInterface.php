<?php

namespace IKTO\PgI\Converter;

interface EncoderGuesserInterface
{
    /**
     * Checks possibility of encoding value by this converter
     *
     * @param mixed $value
     * @return bool
     */
    public function canEncode($value);
}
