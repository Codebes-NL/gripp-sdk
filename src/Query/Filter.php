<?php

namespace CodeBes\GrippSdk\Query;

class Filter
{
    protected string $field;

    protected string $operator;

    protected mixed $value;

    public function __construct(string $field, string $operator, mixed $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
