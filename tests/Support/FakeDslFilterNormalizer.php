<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Support;

use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

final class FakeDslFilterNormalizer implements DslFilterNormalizer
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $rules
     * @param array<string, mixed> $options
     */
    public function normalize(array $payload, array $rules, array $options = []): DslNormalizedFilterSet
    {
        $normalizedPayload = $payload;
        $items = [];
        $errors = [];

        foreach ($rules as $payloadField => $rule) {
            $rule = trim($rule);
            $valueInfo = $this->getArrayValueByPath($normalizedPayload, $payloadField);

            if (str_contains($rule, 'default:') && !$valueInfo['exists']) {
                $default = $this->ruleArgument($rule, 'default:');
                $this->setArrayValueByPath($normalizedPayload, $payloadField, $default);
                $valueInfo = ['exists' => true, 'value' => $default];
            }

            if (str_contains($rule, 'required') && !$valueInfo['exists']) {
                $errors[] = '请输入' . $payloadField;
                continue;
            }

            if (!$valueInfo['exists']) {
                continue;
            }

            $value = $valueInfo['value'];
            if (str_contains($rule, 'trim') && is_string($value)) {
                $value = trim($value);
                $this->setArrayValueByPath($normalizedPayload, $payloadField, $value);
            }

            $items[$payloadField] = new DslNormalizedFilterItem($payloadField, $this->normalizeQueryValues($value));
        }

        if ($errors !== []) {
            return DslNormalizedFilterSet::failure($errors);
        }

        return DslNormalizedFilterSet::success($normalizedPayload, $items);
    }

    private function ruleArgument(string $rule, string $prefix): string
    {
        foreach (explode('|', $rule) as $segment) {
            $segment = trim($segment);
            if (str_starts_with($segment, $prefix)) {
                return substr($segment, strlen($prefix));
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     * @return array{exists: bool, value: mixed}
     */
    private function getArrayValueByPath(array $data, string $path): array
    {
        if ($path === '') {
            return ['exists' => true, 'value' => $data];
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return ['exists' => false, 'value' => null];
            }
            $current = $current[$segment];
        }

        return ['exists' => true, 'value' => $current];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setArrayValueByPath(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $current = &$data;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeQueryValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn (mixed $item): bool => $this->hasMeaningfulValue($item)));
        }

        return $this->hasMeaningfulValue($value) ? [$value] : [];
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value || $value === '0' || $value === 0;
    }
}
