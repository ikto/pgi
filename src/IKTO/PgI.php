<?php

namespace IKTO;

use IKTO\PgI\Database;

class PgI
{
    public static function connect($dsn, $user = null, $password = null)
    {
        return new Database($dsn, $user, $password);
    }
}
