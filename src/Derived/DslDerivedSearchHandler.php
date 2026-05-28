<?php

namespace HongXunPan\EloquentQueryDsl\Derived;

use Closure;
use HongXunPan\EloquentQueryDsl\Condition\DslSearchCondition;
use Illuminate\Database\Eloquent\Builder;
use ReflectionFunction;

/**
 * Query DSL 派生 search 自定义处理器。
 *
 * 当前兼容旧 DSL 常见签名：
 * - (Builder $query, string $value, string $mode)
 * 同时也允许按需读取 canonicalField / field。
 */
class DslDerivedSearchHandler implements DslDerivedSearchBehavior
{
    protected Closure $handler;

    private function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    public static function make(callable $handler): self
    {
        return new self($handler);
    }

    public function apply(Builder $query, DslSearchCondition $condition): void
    {
        $arguments = [
            $query,
            (string)$condition->value(),
            $condition->mode(),
            $condition->canonicalField(),
            $condition->fieldPath()->field(),
        ];

        $reflection = new ReflectionFunction($this->handler);
        if ($reflection->isVariadic()) {
            ($this->handler)(...$arguments);
            return;
        }

        ($this->handler)(...array_slice($arguments, 0, $reflection->getNumberOfParameters()));
    }
}
