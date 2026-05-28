<?php

namespace HongXunPan\EloquentQueryDsl\Page;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;

/**
 * DSL 分页事实策略。
 *
 * 本包不执行 count / paginate，但会用该策略限制最终暴露给使用侧的分页事实，
 * 避免超大 limit / export_limit 被误用到业务仓分页执行层。
 */
final class DslPaginationPolicy
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_LIMIT = 20;
    public const DEFAULT_MAX_LIMIT = 100;
    public const DEFAULT_MAX_EXPORT_LIMIT = 1000;

    private function __construct(
        private int $defaultPage = self::DEFAULT_PAGE,
        private int $defaultLimit = self::DEFAULT_LIMIT,
        private int $maxLimit = self::DEFAULT_MAX_LIMIT,
        private int $maxExportLimit = self::DEFAULT_MAX_EXPORT_LIMIT,
        private bool $exportLimitEnabled = true,
        private bool $numericStringEnabled = true,
        private bool $floatEnabled = false,
    ) {
        $this->assertPositive($this->defaultPage, 'defaultPage');
        $this->assertPositive($this->defaultLimit, 'defaultLimit');
        $this->assertPositive($this->maxLimit, 'maxLimit');
        $this->assertPositive($this->maxExportLimit, 'maxExportLimit');
    }

    public static function default(): self
    {
        return new self();
    }

    public function withDefaultPage(int $page): self
    {
        $policy = clone $this;
        $policy->assertPositive($page, 'defaultPage');
        $policy->defaultPage = $page;

        return $policy;
    }

    public function withDefaultLimit(int $limit): self
    {
        $policy = clone $this;
        $policy->assertPositive($limit, 'defaultLimit');
        $policy->defaultLimit = min($limit, $policy->maxLimit);

        return $policy;
    }

    public function withMaxLimit(int $limit): self
    {
        $policy = clone $this;
        $policy->assertPositive($limit, 'maxLimit');
        $policy->maxLimit = $limit;
        $policy->defaultLimit = min($policy->defaultLimit, $limit);

        return $policy;
    }

    public function withMaxExportLimit(int $limit): self
    {
        $policy = clone $this;
        $policy->assertPositive($limit, 'maxExportLimit');
        $policy->maxExportLimit = $limit;

        return $policy;
    }

    public function withExportLimitEnabled(bool $enabled): self
    {
        $policy = clone $this;
        $policy->exportLimitEnabled = $enabled;

        return $policy;
    }

    public function withoutExportLimit(): self
    {
        return $this->withExportLimitEnabled(false);
    }

    public function withNumericStringEnabled(bool $enabled): self
    {
        $policy = clone $this;
        $policy->numericStringEnabled = $enabled;

        return $policy;
    }

    public function withFloatEnabled(bool $enabled): self
    {
        $policy = clone $this;
        $policy->floatEnabled = $enabled;

        return $policy;
    }

    public function defaultPage(): int
    {
        return $this->defaultPage;
    }

    public function defaultLimit(): int
    {
        return min($this->defaultLimit, $this->maxLimit);
    }

    public function maxLimit(): int
    {
        return $this->maxLimit;
    }

    public function maxExportLimit(): int
    {
        return $this->maxExportLimit;
    }

    public function exportLimitEnabled(): bool
    {
        return $this->exportLimitEnabled;
    }

    public function normalizePage(mixed $value): ?int
    {
        return $this->normalizePositiveInt($value, null);
    }

    public function normalizeLimit(mixed $value): ?int
    {
        return $this->normalizePositiveInt($value, $this->maxLimit);
    }

    public function normalizeExportLimit(mixed $value): ?int
    {
        if (!$this->exportLimitEnabled) {
            return null;
        }

        return $this->normalizePositiveInt($value, $this->maxExportLimit);
    }

    private function normalizePositiveInt(mixed $value, ?int $max): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return null;
        }

        if (is_int($value)) {
            return $this->normalizeBoundedInt($value, $max);
        }

        if (is_float($value)) {
            if (!$this->floatEnabled || floor($value) !== $value) {
                return null;
            }

            return $this->normalizeBoundedInt((int)$value, $max);
        }

        if (!is_string($value) || !$this->numericStringEnabled) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !preg_match('/^[0-9]+$/', $value)) {
            return null;
        }

        $value = ltrim($value, '0');
        if ($value === '') {
            return null;
        }

        if ($max !== null && strlen($value) > strlen((string)$max)) {
            return $max;
        }

        return $this->normalizeBoundedInt((int)$value, $max);
    }

    private function normalizeBoundedInt(int $value, ?int $max): ?int
    {
        if ($value <= 0) {
            return null;
        }

        return $max === null ? $value : min($value, $max);
    }

    private function assertPositive(int $value, string $name): void
    {
        if ($value <= 0) {
            throw DslQueryDslDefinitionException::fromMessage(
                'query dsl pagination policy ' . $name . ' 必须为正整数',
            );
        }
    }
}
