<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * QueryDSL V2 查询输入 section。
 *
 * 该对象只承接 section 名称与原始值，不预设 search / filter / sort 等具体业务含义。
 */
class DslQuerySection
{
    protected string $name;
    protected mixed $value;

    private function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * 创建通用查询 section。
     */
    public static function make(string $name, mixed $value): self
    {
        $name = trim($name);
        if ($name === '') {
            throw DslQueryDslException::emptyQuerySectionName();
        }

        return new self($name, $value);
    }

    /**
     * 返回 section 名称。
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * 返回 section 原始值，具体结构由后续 Definition 判断。
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * 判断 section 值是否为数组。
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * 判断 section 值是否为空数组。
     */
    public function isEmptyArray(): bool
    {
        return $this->value === [];
    }
}
