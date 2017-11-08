<?php

namespace IKTO\PgI\Database;

interface DatabaseAwareInterface
{
    /**
     * Sets database object
     *
     * @param DatabaseInterface $db The database object
     */
    public function setDatabase(DatabaseInterface $db);
}
