<?php

namespace LibSQLPDO;

class PDO extends \PDO
{
    private ?\LibSQLTransaction $tx;
    private bool $in_transaction = false;
    private ?\LibSQL $db;

    public function __construct(
        string $dsn,
        ?string $username = null,
        #[\SensitiveParameter]
        ?string $password = null,
        #[\SensitiveParameter]
        ?array $options = []
    ) {
        $config = match (true) {
            $dsn == ':memory:' => ['url' => $dsn],
            str_starts_with($dsn, 'libsql:') => parseDSNForLibSQL($dsn),
            default => configBuilder($dsn, $password, $options),
        };
        $this->db = new \LibSQL($config);
        $this->in_transaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->in_transaction;
    }

    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            throw new \PDOException("Already in a transaction");
        }

        $this->in_transaction = true;
        $this->tx = $this->db->transaction();

        return true;
    }

    public function exec(string $statement): int
    {
        return $this->db->execute($statement);
    }

    public function commit(): bool
    {
        if (!$this->inTransaction()) {
            throw new \PDOException("No active transaction");
        }

        $this->tx->commit();
        $this->in_transaction = false;

        return true;
    }

    public function rollback(): bool
    {
        if (!$this->inTransaction()) {
            throw new \PDOException("No active transaction");
        }

        $this->tx->rollback();
        $this->in_transaction = false;

        return true;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new PDOStatement(
            ($this->inTransaction() ? $this->tx : $this->db)->prepare($query)
        );
    }

    public function errorCode(): ?string
    {
        throw new \Exception('Not implemented');
    }

    public function errorInfo(): array
    {
        throw new \Exception('Not implemented');
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->db->lastInsertedId();
    }

    public function close()
    {
        $this->db->close();
    }
}
