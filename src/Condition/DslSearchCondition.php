<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * QueryDSL V2 搜索条件。
 *
 * 只描述搜索字段、搜索值与搜索模式，不负责把搜索模式翻译成 Builder 调用。
 */
class DslSearchCondition extends DslFieldCondition
{
    public const SECTION = 'search';

    protected string $mode;

    protected function __construct(DslFieldPath $fieldPath, mixed $value, string $mode = '')
    {
        parent::__construct(self::SECTION, $fieldPath, $value);
        $this->mode = trim($mode);
    }

    public static function fromField(DslFieldPath $fieldPath, mixed $value, string $mode = ''): self
    {
        return new self($fieldPath, $value, $mode);
    }

    /**
     * 返回搜索模式；空字符串表示后续解释阶段使用默认模式。
     */
    public function mode(): string
    {
        return $this->mode;
    }
}
