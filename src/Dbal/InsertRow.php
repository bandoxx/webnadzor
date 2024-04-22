<?php

namespace App\Dbal;

class InsertRow
{
    private array $values = [];

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * @param string $name
     * @param $value
     * @return self
     */
    public function set(string $name, $value): self
    {
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->values[$name] ?? null;
    }

    public function getAll()
    {
        return $this->values;
    }
}
