<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\PgConnectionAwareInterface;

class PgBytea implements ConverterInterface, PgConnectionAwareInterface
{
    private $pgConnection;

    public function setPgConnection($pgConnection)
    {
        $this->pgConnection = $pgConnection;
    }

    public function encode($value, $type = null)
    {
        return pg_escape_bytea($this->pgConnection, $value);
    }

    public function decode($value, $type = null)
    {
        return pg_unescape_bytea($value);
    }
}
