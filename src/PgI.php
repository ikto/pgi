<?php

namespace IKTO;

use IKTO\PgI\Database\Database;

class PgI
{
    const TRANSACTION_INACTIVE      = 0;
    const TRANSACTION_ACTIVE        = 1;
    const TRANSACTION_ERROR         = 2;

    public static function connect($dsn, $user = null, $password = null)
    {
        return new Database($dsn, $user, $password);
    }
}
