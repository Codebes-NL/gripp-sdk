<?php

namespace CodeBes\GrippSdk\Transport;

use Illuminate\Support\Collection;

class JsonRpcResponse
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function result(): mixed
    {
        return $this->data['result'] ?? null;
    }

    public function rows(): array
    {
        $result = $this->result();

        if (is_array($result) && isset($result['rows'])) {
            return $result['rows'];
        }

        return [];
    }

    public function hasMoreItems(): bool
    {
        $result = $this->result();

        return is_array($result) && ($result['more_items_in_collection'] ?? false);
    }

    public function recordId(): ?int
    {
        $result = $this->result();

        if (is_array($result) && isset($result['id'])) {
            return (int) $result['id'];
        }

        return null;
    }

    public function count(): int
    {
        $result = $this->result();

        if (is_array($result) && isset($result['count'])) {
            return (int) $result['count'];
        }

        return count($this->rows());
    }

    public function toCollection(): Collection
    {
        return new Collection($this->rows());
    }

    public function error(): ?array
    {
        return $this->data['error'] ?? null;
    }

    public function hasError(): bool
    {
        return isset($this->data['error']);
    }

    public function id(): mixed
    {
        return $this->data['id'] ?? null;
    }

    public function raw(): array
    {
        return $this->data;
    }
}
