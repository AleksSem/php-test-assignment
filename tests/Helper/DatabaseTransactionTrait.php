<?php

namespace App\Tests\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTransactionTrait
{
    private bool $transactionStarted = false;

    protected function beginDatabaseTransaction(): void
    {
        if (!$this->transactionStarted) {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $this->transactionStarted = true;
        }
    }

    protected function rollbackDatabaseTransaction(): void
    {
        if ($this->transactionStarted) {
            $this->getEntityManager()->getConnection()->rollBack();
            $this->transactionStarted = false;
            $this->getEntityManager()->clear();
        }
    }

    abstract protected function getEntityManager(): EntityManagerInterface;
}