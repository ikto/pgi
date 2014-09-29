<?php

namespace IKTO\PgI;

interface PgConnectionAwareInterface
{
    /**
     * Sets postgres connection resource in class
     *
     * @param resource $pgConnection
     */
    public function setPgConnection($pgConnection);
}
