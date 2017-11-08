<?php

namespace IKTO\PgI\Statement;

use IKTO\PgI\Database\DatabaseInterface;

class Prepared extends Plain
{
    /* @var string */
    protected $name;

    public function __construct(DatabaseInterface $db, $query)
    {
        parent::__construct($db, $query);

        $this->prepare();
    }

    public function __destruct()
    {
        $this->db->pgDeallocate($this->name);
    }

    public function __clone()
    {
        $this->prepare();
    }

    public function execute(array $params = array())
    {
        $this->result = $this->db->pgExecute($this->name, $this->getParams($params));

        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);

            return true;
        }

        return false;
    }

    protected function prepare()
    {
        $this->name = $this->db->getPreparedStatementName($this->query);

        $this->db->pgPrepare($this->name, $this->query);
    }
}
