<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 区间条件。
 *
 * 只描述字段区间的起止值，不负责校验区间合法性或执行 whereBetween。
 */
class DslBetweenCondition extends DslCondition
{
    public const SECTION = 'between';

    protected mixed $startValue;
    protected mixed $endValue;

    protected function __construct(DslFieldPath $fieldPath, mixed $startValue, mixed $endValue)
    {
        parent::__construct(self::SECTION, $fieldPath);
        $this->startValue = $startValue;
        $this->endValue = $endValue;
    }

    public static function make(DslFieldPath $fieldPath, mixed $startValue, mixed $endValue): self
    {
        return new self($fieldPath, $startValue, $endValue);
    }

    public function startValue(): mixed
    {
        return $this->startValue;
    }

    public function endValue(): mixed
    {
        return $this->endValue;
    }

    /**
     * 返回起止值数组，仅作为只读访问便利，不作为内部条件黑盒传递。
     *
     * @return array{0: mixed, 1: mixed}
     */
    public function values(): array
    {
        return [$this->startValue, $this->endValue];
    }
}
