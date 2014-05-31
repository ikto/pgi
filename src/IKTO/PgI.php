<?php

namespace IKTO;

use IKTO\PgI\Database;
use IKTO\PgI\ParamsEncoder;

class PgI
{
    const TRANSACTION_INACTIVE      = 0;
    const TRANSACTION_ACTIVE        = 1;
    const TRANSACTION_ERROR         = 2;

    const PARAM_BYTEA         = ParamsEncoder::BYTEA;
    const PARAM_JSON          = ParamsEncoder::JSON;
    const PARAM_TIMESTAMP     = ParamsEncoder::TIMESTAMP;
    const PARAM_TIMESTAMPTZ   = ParamsEncoder::TIMESTAMPTZ;

    public static function connect($dsn, $user = null, $password = null)
    {
        return new Database($dsn, $user, $password);
    }
}
