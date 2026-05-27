<?php

namespace HongXunPan\EloquentQueryDsl\Filter\Value;

/**
 * filter payload 归一化结果集合。
 */
class DslNormalizedFilterSet
{
    protected bool $passed;

    /**
     * @var array<int, string>
     */
    protected array $errors;

    /**
     * @var array<string, mixed>
     */
    protected array $normalizedPayload;

    /**
     * @var array<string, DslNormalizedFilterItem>
     */
    protected array $items;

    /**
     * @param array<int, string> $errors
     * @param array<string, mixed> $normalizedPayload
     * @param array<string, DslNormalizedFilterItem> $items
     */
    private function __construct(bool $passed, array $errors, array $normalizedPayload, array $items)
    {
        $this->passed = $passed;
        $this->errors = array_values($errors);
        $this->normalizedPayload = $normalizedPayload;
        $this->items = $items;
    }

    /**
     * @param array<string, mixed> $normalizedPayload
     * @param array<string, DslNormalizedFilterItem> $items
     */
    public static function success(array $normalizedPayload, array $items): self
    {
        return new self(true, [], $normalizedPayload, $items);
    }

    /**
     * @param array<int, string> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors, [], []);
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function isFailed(): bool
    {
        return !$this->passed;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedPayload(): array
    {
        return $this->normalizedPayload;
    }

    public function has(string $payloadField): bool
    {
        return $this->item($payloadField) !== null;
    }

    public function item(string $payloadField): ?DslNormalizedFilterItem
    {
        $payloadField = trim($payloadField);
        if ($payloadField === '') {
            return null;
        }

        return $this->items[$payloadField] ?? null;
    }

    /**
     * @return array<string, DslNormalizedFilterItem>
     */
    public function items(): array
    {
        return $this->items;
    }
}

