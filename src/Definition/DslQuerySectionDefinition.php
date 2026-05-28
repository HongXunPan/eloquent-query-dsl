<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL section 能力定义。
 *
 * 该对象只声明一个 section 可用的字段集合，不固定 section 名称必须是 search / filter / sort。
 */
class DslQuerySectionDefinition
{
    protected string $name;

    /**
     * @var array<string, DslQueryFieldDefinition>
     */
    protected array $fields = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl section名称不能为空');
        }

        return new self($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function allowField(DslFieldPath $fieldPath): DslQueryFieldDefinition
    {
        $canonical = $fieldPath->canonical();
        if (!array_key_exists($canonical, $this->fields)) {
            $this->fields[$canonical] = DslQueryFieldDefinition::make($fieldPath);
        }

        return $this->fields[$canonical];
    }

    public function hasField(string $canonicalField): bool
    {
        return array_key_exists($canonicalField, $this->fields);
    }

    public function field(string $canonicalField): ?DslQueryFieldDefinition
    {
        return $this->fields[$canonicalField] ?? null;
    }

    /**
     * @return array<string, DslQueryFieldDefinition>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function fieldNames(): array
    {
        return array_keys($this->fields);
    }
}
