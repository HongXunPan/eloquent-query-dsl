<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Condition\DslSortCondition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;

/**
 * QueryDSL V2 排序方向策略。
 *
 * 该对象只描述某个 sort 字段允许的排序方向限制，
 * 不负责解析 sort item，也不直接执行排序。
 */
class DslSortDirectionPolicy
{
    public const ANY = 'any';
    public const ASC_ONLY = 'asc_only';
    public const DESC_ONLY = 'desc_only';

    protected string $mode;

    private function __construct(string $mode)
    {
        $this->mode = self::normalizeMode($mode);
    }

    public static function any(): self
    {
        return new self(self::ANY);
    }

    public static function ascOnly(): self
    {
        return new self(self::ASC_ONLY);
    }

    public static function descOnly(): self
    {
        return new self(self::DESC_ONLY);
    }

    public static function from(string $mode): self
    {
        return new self($mode);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function allows(string $order): bool
    {
        $order = strtolower(trim($order));
        if ($this->mode === self::ASC_ONLY) {
            return $order === DslSortCondition::ORDER_ASC;
        }

        if ($this->mode === self::DESC_ONLY) {
            return $order === DslSortCondition::ORDER_DESC;
        }

        return true;
    }

    protected static function normalizeMode(string $mode): string
    {
        $mode = trim($mode);
        if (!in_array($mode, [self::ANY, self::ASC_ONLY, self::DESC_ONLY], true)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 排序方向策略错误');
        }

        return $mode;
    }
}
