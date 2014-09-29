<?php

namespace IKTO\PgI\Converter;

interface ConverterInterface
{
    /**
     * Converts data from php to postgres
     *
     * @param mixed $value The input data
     * @param null|string $type The optional type (if needed)
     * @return mixed The output data to bind as query param
     */
    public function encode($value, $type = null);

    /**
     * Converts data from postgres to php
     *
     * @param mixed $value The data received from postgres
     * @param null|string $type The optional type (if needed)
     * @return mixed The output data to store in php variable
     */
    public function decode($value, $type = null);
}
