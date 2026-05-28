<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 排序条件。
 *
 * 只描述排序字段与排序方向，不负责执行 orderBy。
 */
class DslSortCondition extends DslCondition
{
    public const SECTION = 'sort';
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';

    protected string $order;

    protected function __construct(DslFieldPath $fieldPath, string $order)
    {
        parent::__construct(self::SECTION, $fieldPath);
        $this->order = self::normalizeOrder($order);
    }

    public static function make(DslFieldPath $fieldPath, string $order): self
    {
        return new self($fieldPath, $order);
    }

    public function order(): string
    {
        return $this->order;
    }

    protected static function normalizeOrder(string $order): string
    {
        $order = strtolower(trim($order));
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC], true)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 排序方向错误');
        }

        return $order;
    }
}
