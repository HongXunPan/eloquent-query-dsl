<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * sort 输入归一化器。
 *
 * 支持字符串简写、字段 map 与标准 sort item list；不负责字段白名单校验。
 *
 * @internal
 */
class DslSortInputNormalizer
{
    public function __construct(private ?DslJsonDecoder $jsonDecoder = null)
    {
        $this->jsonDecoder ??= new DslJsonDecoder();
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->jsonDecoder->tryDecode($value, $decoded)) {
            $value = $decoded;
        }

        if (is_string($value)) {
            return [$this->fromString($value)];
        }

        if (!is_array($value) || $value === [] || $this->jsonDecoder->isListArray($value)) {
            return $value;
        }

        $items = [];
        foreach ($value as $field => $order) {
            if (!is_string($field) || trim($field) === '') {
                throw DslQueryDslException::invalidSectionFormat('sort');
            }

            $items[] = [
                'field' => trim($field),
                'order' => strtolower(trim((string)$order)) ?: 'asc',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    private function fromString(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            throw DslQueryDslException::invalidSectionFormat('sort');
        }

        $order = 'asc';
        if (str_starts_with($value, '-')) {
            $order = 'desc';
            $value = substr($value, 1);
        } elseif (str_starts_with($value, '+')) {
            $value = substr($value, 1);
        }

        if (str_contains($value, ':')) {
            [$value, $order] = array_map('trim', explode(':', $value, 2));
            $order = strtolower($order);
        }

        if ($value === '') {
            throw DslQueryDslException::invalidSectionFormat('sort');
        }

        return [
            'field' => $value,
            'order' => $order,
        ];
    }
}
