<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * QueryDSL V2 默认排序定义。
 *
 * 该对象只描述服务端默认排序声明，不承接请求排序条件。
 */
class DslQueryDefaultSort
{
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';

    protected DslFieldPath $fieldPath;
    protected string $order;

    private function __construct(DslFieldPath $fieldPath, string $order)
    {
        $this->fieldPath = $fieldPath;
        $this->order = $order;
    }

    public static function make(DslFieldPath $fieldPath, string $order): self
    {
        $order = strtolower(trim($order));
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC], true)) {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 默认排序方向错误');
        }

        return new self($fieldPath, $order);
    }

    public static function forField(
        string $field,
        string $mainEntity,
        string $order = self::ORDER_ASC
    ): self {
        return self::make(
            DslFieldPath::fromDefinition($field, $mainEntity),
            $order
        );
    }

    public static function asc(string $field, string $mainEntity): self
    {
        return self::forField($field, $mainEntity, self::ORDER_ASC);
    }

    public static function desc(string $field, string $mainEntity): self
    {
        return self::forField($field, $mainEntity, self::ORDER_DESC);
    }

    public function fieldPath(): DslFieldPath
    {
        return $this->fieldPath;
    }

    public function order(): string
    {
        return $this->order;
    }
}
