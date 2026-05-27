<?php

namespace HongXunPan\EloquentQueryDsl\Field;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * QueryDSL V2 字段路径对象。
 *
 * 该对象只承接字段路径解析，不解析查询输入，不修改 Builder，也不承接资源业务语义。
 */
class DslFieldPath
{
    protected string $originalField;
    protected string $mainEntity;
    protected string $entity;
    protected string $field;
    protected string $canonicalField;

    private function __construct(
        string $originalField,
        string $mainEntity,
        string $entity,
        string $field
    ) {
        $this->originalField = $originalField;
        $this->mainEntity = $mainEntity;
        $this->entity = $entity;
        $this->field = $field;
        $this->canonicalField = $entity . '.' . $field;
    }

    /**
     * 基于当前主实体解析字段路径。
     *
     * - `name` 会被解析为 `<主实体>.name`
     * - `activity.name` 会保留显式实体
     * - `alumni_card.real_name` 会被识别为 relation 字段路径
     */
    public static function fromDefinition(string $field, string $mainEntity): self
    {
        $mainEntity = trim($mainEntity);
        if ($mainEntity === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 主实体不能为空');
        }

        $originalField = $field;
        $field = trim($field);
        if ($field === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 字段不能为空');
        }

        if (!str_contains($field, '.')) {
            return new self($originalField, $mainEntity, $mainEntity, $field);
        }

        [$entity, $actualField] = explode('.', $field, 2);
        $entity = trim($entity);
        $actualField = trim($actualField);
        if ($entity === '' || $actualField === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 字段格式错误：' . $field);
        }

        return new self($originalField, $mainEntity, $entity, $actualField);
    }

    public static function fromInput(string $field, string $mainEntity): self
    {
        $mainEntity = trim($mainEntity);
        if ($mainEntity === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 主实体不能为空');
        }

        $originalField = $field;
        $field = trim($field);
        if ($field === '') {
            throw DslQueryDslException::invalidFieldFormat();
        }

        if (!str_contains($field, '.')) {
            return new self($originalField, $mainEntity, $mainEntity, $field);
        }

        [$entity, $actualField] = explode('.', $field, 2);
        $entity = trim($entity);
        $actualField = trim($actualField);
        if ($entity === '' || $actualField === '') {
            throw DslQueryDslException::invalidFieldFormat();
        }

        return new self($originalField, $mainEntity, $entity, $actualField);
    }

    /**
     * 返回调用方传入的原始字段字符串。
     */
    public function originalField(): string
    {
        return $this->originalField;
    }

    /**
     * 返回当前 DSL 上下文的主实体别名。
     */
    public function mainEntity(): string
    {
        return $this->mainEntity;
    }

    /**
     * 返回字段所属实体别名。
     */
    public function entity(): string
    {
        return $this->entity;
    }

    /**
     * 返回不含实体前缀的实际字段名。
     */
    public function field(): string
    {
        return $this->field;
    }

    /**
     * 返回标准化字段路径：`entity.field`。
     */
    public function canonical(): string
    {
        return $this->canonicalField;
    }

    /**
     * 判断该字段是否属于主实体。
     */
    public function isMainEntity(): bool
    {
        return $this->entity === $this->mainEntity;
    }

    /**
     * 判断该字段是否属于 relation 实体。
     */
    public function isRelation(): bool
    {
        return !$this->isMainEntity();
    }
}
