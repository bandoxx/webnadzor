<?php

namespace App\Dbal;

use Doctrine\DBAL\Connection;

class MultipleInsertExecutor
{
    private Connection $connection;

    /** @var string */
    private string $table;

    /** @var string[] */
    private array $fields = [];

    /** @var InsertRow[] */
    private array $rows = [];

    /** @var string[] */
    private array $parameters = [];

    /**
     * @param string[] $fields
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function enqueueData(array $data): self
    {
        $row = new InsertRow($data);

        return $this->enqueue($row);
    }

    /**
     * @param InsertRow $row
     * @return self
     */
    public function enqueue(InsertRow $row): self
    {
        foreach ($row->getAll() as $name => $value) {
            if (!in_array($name, $this->fields)) {
                $this->fields[] = $name;
            }
        }

        $this->rows[] = $row;

        return $this;
    }

    public function execute(): int
    {
        if (0 == count($this->rows) || 0 == count($this->fields)) {
            return 0;
        }

        $sql = $this->buildSql();

        if (null === $sql) {
            return 0;
        }

        $return = $this->connection->executeStatement($sql, $this->parameters);

        $this->clearRows();

        return $return;
    }

    public function countRows(): int
    {
        return count($this->rows);
    }

    public function clearRows(): void
    {
        $this->rows = [];
        $this->parameters = [];
    }

    private function buildSql(): ?string
    {
        $sqlRows = [];

        foreach ($this->rows as $row) {
            $sqlRowData = [];

            foreach ($this->fields as $field) {
                $sqlRowData[] = $this->getSqlParameter($row->get($field));
            }

            $sqlRows[] = '( ' . implode(' , ', $sqlRowData) . ' )';
        }

        if (count($sqlRows) == 0) {
            return null;
        }

        return "INSERT INTO `{$this->table}` ( {$this->buildFieldNames()} ) VALUES " . implode(' , ', $sqlRows);
    }

    private function buildFieldNames(): string
    {
        $fields = array_map(function ($name) {
            return '`' . $name . '`';
        }, $this->fields);

        return implode(', ', $fields);
    }

    private function getSqlParameter($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . addslashes((string) $value) . "'";

        // It allows maximum 60k parameters and it's not enough
//        $parameterName = 'param' . count($this->parameters);
//        $this->parameters[$parameterName] = (string) $value;
//
//        return ':' . $parameterName;
    }
}
