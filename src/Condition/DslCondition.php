<?php

namespace HongXunPan\EloquentQueryDsl\Condition;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL 条件对象基类。
 *
 * 条件对象只描述已解析后的字段条件，不承接请求输入解析，也不修改 Builder。
 */
abstract class DslCondition
{
    protected string $sectionName;
    protected DslFieldPath $fieldPath;

    protected function __construct(string $sectionName, DslFieldPath $fieldPath)
    {
        $sectionName = trim($sectionName);
        if ($sectionName === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl 条件 section 不能为空');
        }

        $this->sectionName = $sectionName;
        $this->fieldPath = $fieldPath;
    }

    /**
     * 返回该条件来源的查询 section 名称。
     */
    public function sectionName(): string
    {
        return $this->sectionName;
    }

    /**
     * 返回条件字段路径。
     */
    public function fieldPath(): DslFieldPath
    {
        return $this->fieldPath;
    }

    /**
     * 返回标准化字段名。
     */
    public function canonicalField(): string
    {
        return $this->fieldPath->canonical();
    }

    /**
     * 判断条件是否来自指定 section。
     */
    public function isForSection(string $sectionName): bool
    {
        return $this->sectionName === trim($sectionName);
    }
}
