<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * DSL 外部输入映射。
 *
 * 该对象只描述“外部参数名如何映射到标准 DSL 输入”，不承接字段白名单、
 * validator、业务默认值或查询执行逻辑。
 */
class DslInputMap
{
    private const MODE_JSON = 'json';
    private const MODE_ARRAY = 'array';
    private const MODE_FLAT = 'flat';

    protected ?string $queryKey = 'query';
    protected string $searchKey = 'search';
    protected string $filterKey = 'filter';
    protected string $betweenKey = 'between';
    protected string $sortKey = 'sort';
    protected string $pageKey = 'page';
    protected string $cursorKey = 'cursor';
    protected string $limitKey = 'limit';
    protected string $exportLimitKey = 'export_limit';
    protected ?string $filterPrefixValue = null;
    protected string $mode = self::MODE_JSON;

    public static function make(): self
    {
        return new self();
    }

    public function query(?string $key): self
    {
        $this->queryKey = $key === null ? null : self::normalizeKey($key);

        return $this;
    }

    public function search(string $key): self
    {
        $this->searchKey = self::normalizeKey($key);

        return $this;
    }

    public function filter(string $key): self
    {
        $this->filterKey = self::normalizeKey($key);

        return $this;
    }

    public function between(string $key): self
    {
        $this->betweenKey = self::normalizeKey($key);

        return $this;
    }

    public function sort(string $key): self
    {
        $this->sortKey = self::normalizeKey($key);

        return $this;
    }

    public function page(string $key): self
    {
        $this->pageKey = self::normalizeKey($key);

        return $this;
    }

    public function cursor(string $key): self
    {
        $this->cursorKey = self::normalizeKey($key);

        return $this;
    }

    public function limit(string $key): self
    {
        $this->limitKey = self::normalizeKey($key);

        return $this;
    }

    public function exportLimit(string $key): self
    {
        $this->exportLimitKey = self::normalizeKey($key);

        return $this;
    }

    public function filterPrefix(string $prefix): self
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            throw DslQueryDslException::invalidQuery('filter prefix不能为空');
        }

        $this->filterPrefixValue = $prefix;

        return $this;
    }

    public function jsonInput(): self
    {
        $this->mode = self::MODE_JSON;
        if ($this->queryKey === null) {
            $this->queryKey = 'query';
        }

        return $this;
    }

    public function arrayInput(): self
    {
        $this->mode = self::MODE_ARRAY;
        $this->queryKey = null;

        return $this;
    }

    public function flatInput(): self
    {
        $this->mode = self::MODE_FLAT;
        $this->queryKey = null;

        return $this;
    }

    public function queryKey(): ?string
    {
        return $this->queryKey;
    }

    public function searchKey(): string
    {
        return $this->searchKey;
    }

    public function filterKey(): string
    {
        return $this->filterKey;
    }

    public function betweenKey(): string
    {
        return $this->betweenKey;
    }

    public function sortKey(): string
    {
        return $this->sortKey;
    }

    public function pageKey(): string
    {
        return $this->pageKey;
    }

    public function cursorKey(): string
    {
        return $this->cursorKey;
    }

    public function limitKey(): string
    {
        return $this->limitKey;
    }

    public function exportLimitKey(): string
    {
        return $this->exportLimitKey;
    }

    /** @return list<string> */
    public function pageKeys(): array
    {
        return [
            $this->pageKey,
            $this->limitKey,
            $this->exportLimitKey,
        ];
    }

    public function assertPaginationKeysValid(): void
    {
        if (in_array($this->cursorKey, $this->pageKeys(), true)) {
            throw DslQueryDslDefinitionException::fromMessage(
                'cursor参数名不能与page、limit或export_limit重复',
            );
        }
    }

    public function filterPrefixValue(): ?string
    {
        return $this->filterPrefixValue;
    }

    public function isFlatInput(): bool
    {
        return $this->mode === self::MODE_FLAT;
    }

    public function isArrayInput(): bool
    {
        return $this->mode === self::MODE_ARRAY;
    }

    public function isJsonInput(): bool
    {
        return $this->mode === self::MODE_JSON;
    }

    protected static function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            throw DslQueryDslException::invalidQuery('input key不能为空');
        }

        return $key;
    }
}
