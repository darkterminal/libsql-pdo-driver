<?php

namespace LibSQLPDO;

class PDOStatement extends \PDOStatement
{
    private ?array $rows = null;
    private ?int $affectedRows = null;
    private ?int $mode = null;
    private array $boundParameters = [];

    public function __construct(private \LibSQLStatement $statement)
    {
    }

    public function fetchColumn(int $columnIndex = 0)
    {
        $row = $this->fetch();
        return $row ? array_values($row)[$columnIndex] : null;
    }

    public function fetch(int $mode = \PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): array|false
    {
        if ($mode === \PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        $parameters = $this->boundParameters;
        if ($this->hasNamedParameters($parameters)) {
            $this->statement->bindNamed($parameters);
        } else {
            $this->statement->bindPositional(array_values($parameters));
        }
        $result = $this->statement->query();
        $rows = $result->fetchArray(\LibSQL::LIBSQL_ASSOC);

        if (!$rows) {
            return false;
        }

        $row = $rows[$cursorOffset];
        $mode = $this->mode ?? $mode;

        return match ($mode) {
            \PDO::FETCH_ASSOC => $row,
            \PDO::FETCH_OBJ => (object) $row,
            \PDO::FETCH_NUM => array_values($row),
            default => $row
        };
    }

    public function fetchAll(int $mode = \PDO::FETCH_ASSOC, mixed ...$args): array|bool|null    
    {
        if ($mode === \PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        // Determine if parameters are named or positional
        $parameters = $this->boundParameters;
        if ($this->hasNamedParameters($parameters)) {
            $this->statement->bindNamed($parameters);
        } else {
            $this->statement->bindPositional(array_values($parameters));
        }

        $result = $this->statement->query();
        $rows = $result->fetchArray(\LibSQL::LIBSQL_ASSOC);

        if ($rows === null) {
            return false;
        }

        $rowValues = \array_map('array_values', $rows);

        return match ($mode) {
            \PDO::FETCH_BOTH => array_merge($rows, $rowValues),
            \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $rows,
            \PDO::FETCH_NUM => $rowValues,
            \PDO::FETCH_OBJ => $rows,
            default => throw new \PDOException('Unsupported fetch mode.'),
        };
    }

    public function bindValue($parameter, $value, $type = PDO::PARAM_STR)
    {
        if (is_int($parameter)) {
            $this->boundParameters[$parameter] = $value;
        } elseif (is_string($parameter)) {
            $this->boundParameters[$parameter] = $value;
        } else {
            throw new \InvalidArgumentException("Parameter must be an integer or string.");
        }
        return $this;
    }

    public function execute(?array $parameters = []): bool
    {
        if (empty($parameters)) {
            $parameters = $this->boundParameters;
        }

        try {
            // Determine if parameters are named or positional
            if ($this->hasNamedParameters($parameters)) {
                $this->statement->bindNamed($parameters);
            } else {
                $this->statement->bindPositional(array_values($parameters));
            }

            $this->statement->execute($parameters);
            return true;
        } catch (\Exception $e) {
            // Handle exceptions as needed
            return false;
        }
    }

    public function columnCount(): int
    {
        return $this->statement->parameterCount();
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function closeCursor(): void
    {
        $this->statement->reset();
    }

    public function rowCount(): int
    {
        return $this->statement->execute() ?: 0;
    }

    public function nextRowset(): bool
    {
        return false;
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        $this->mode = $mode;

        return true;
    }

    private function hasNamedParameters(array $parameters): bool
    {
        foreach (array_keys($parameters) as $key) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }
}
