<?php

namespace HongXunPan\EloquentQueryDsl\Definition;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;

/**
 * QueryDSL V2 relation 能力定义。
 *
 * 该对象只声明 DSL 实体别名与 ORM relation 方法名的映射，不承接查询执行。
 */
class DslRelationDefinition
{
    protected string $entity;
    protected string $relation;

    private function __construct(string $entity, string $relation)
    {
        $this->entity = $entity;
        $this->relation = $relation;
    }

    public static function make(string $entity, string $relation): self
    {
        $entity = trim($entity);
        $relation = trim($relation);
        if ($entity === '' || $relation === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 关联实体与 relation 不能为空');
        }

        return new self($entity, $relation);
    }

    public function entity(): string
    {
        return $this->entity;
    }

    public function relation(): string
    {
        return $this->relation;
    }
}
