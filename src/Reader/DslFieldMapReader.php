<?php

namespace HongXunPan\EloquentQueryDsl\Reader;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;

/**
 * QueryDSL V2 字段 map 型 section 读取器。
 *
 * 只负责读取形如 search / filter / between 的字段 map 输入；
 * 不理解字段值语义，不归一化 filter value，也不修改 Builder。
 */
class DslFieldMapReader
{
    protected DslSectionReader $sectionReader;

    public function __construct(?DslSectionReader $sectionReader = null)
    {
        $this->sectionReader = $sectionReader ?? new DslSectionReader();
    }

    public function read(DslQueryInput $input, string $sectionName): ?array
    {
        $section = $input->get($sectionName);
        if ($section === null) {
            return null;
        }

        $value = $section->value();
        if (!is_array($value) || ($value !== [] && $this->sectionReader->isListArray($value))) {
            throw DslQueryDslException::invalidSectionFormat($sectionName);
        }

        return $value;
    }

    public function each(DslQueryDefinition $definition, array $sectionValue, callable $handle): void
    {
        foreach ($sectionValue as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw DslQueryDslException::invalidFieldFormat();
            }

            $key = trim($key);
            if ($this->isExplicitEntityNode($definition, $key, $value)) {
                foreach ($value as $field => $fieldValue) {
                    if (!is_string($field) || trim($field) === '') {
                        throw DslQueryDslException::invalidFieldFormat();
                    }

                    $handle(
                        DslFieldPath::fromInput($key . '.' . trim($field), $definition->mainEntity()),
                        $fieldValue
                    );
                }
                continue;
            }

            $handle(DslFieldPath::fromInput($key, $definition->mainEntity()), $value);
        }
    }

    protected function isExplicitEntityNode(DslQueryDefinition $definition, string $key, mixed $value): bool
    {
        if (!is_array($value) || $this->sectionReader->isListArray($value)) {
            return false;
        }

        return $key === $definition->mainEntity() || $definition->hasRelation($key);
    }
}
