<?php

namespace HongXunPan\EloquentQueryDsl\Filter\Value;

/**
 * 单个 filter 字段的归一化结果。
 */
class DslNormalizedFilterItem
{
    protected string $payloadField;

    /**
     * @var array<int, mixed>
     */
    protected array $normalizedValues;

    /**
     * @param array<int, mixed> $normalizedValues
     */
    public function __construct(string $payloadField, array $normalizedValues)
    {
        $this->payloadField = trim($payloadField);
        $this->normalizedValues = array_values($normalizedValues);
    }

    public function payloadField(): string
    {
        return $this->payloadField;
    }

    /**
     * @return array<int, mixed>
     */
    public function normalizedValues(): array
    {
        return $this->normalizedValues;
    }

    public function hasMeaningfulValue(): bool
    {
        return $this->normalizedValues !== [];
    }
}
