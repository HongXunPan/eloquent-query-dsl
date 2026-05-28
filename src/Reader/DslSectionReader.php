<?php

namespace HongXunPan\EloquentQueryDsl\Reader;

use HongXunPan\EloquentQueryDsl\Definition\DslQueryDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQueryFieldDefinition;
use HongXunPan\EloquentQueryDsl\Definition\DslQuerySectionDefinition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;

/**
 * Query DSL section 定义读取器。
 *
 * 只负责 Definition 中 section / field 能力读取与 strict 模式错误收口；
 * 不解析具体 search / filter / between / sort 语义，也不修改 Builder。
 *
 * @internal
 */
class DslSectionReader
{
    public function fieldDefinition(
        DslQueryDefinition $definition,
        string $sectionName,
        DslFieldPath $fieldPath,
        mixed $value,
    ): ?DslQueryFieldDefinition {
        $sectionDefinition = $this->sectionDefinition($definition, $sectionName, $value);
        if ($sectionDefinition === null) {
            return null;
        }

        $fieldDefinition = $sectionDefinition->field($fieldPath->canonical());
        if ($fieldDefinition === null) {
            $this->rejectUnknownField($definition, $sectionName, $fieldPath->canonical());
            return null;
        }

        return $fieldDefinition;
    }

    public function sectionDefinition(
        DslQueryDefinition $definition,
        string $sectionName,
        mixed $value,
    ): ?DslQuerySectionDefinition {
        $sectionDefinition = $definition->getSection($sectionName);
        if ($sectionDefinition !== null) {
            return $sectionDefinition;
        }

        if ($this->hasMeaningfulValue($value)) {
            $this->rejectDisabledSection($definition, $sectionName);
        }

        return null;
    }

    public function rejectDisabledSection(DslQueryDefinition $definition, string $sectionName): void
    {
        if ($definition->isStrict()) {
            throw DslQueryDslException::disabledSection($sectionName);
        }
    }

    public function rejectUnknownField(DslQueryDefinition $definition, string $sectionName, string $field): void
    {
        if ($definition->isStrict()) {
            throw DslQueryDslException::unknownField($sectionName, $field);
        }
    }

    public function rejectInvalidInput(
        DslQueryDefinition $definition,
        string $sectionName,
        string $field,
        string $message,
    ): void {
        if ($definition->isStrict()) {
            throw DslQueryDslException::invalidField($sectionName, $field, $message);
        }
    }

    public function hasMeaningfulValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value || $value === '0' || $value === 0;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function isListArray(array $data): bool
    {
        $index = 0;
        foreach ($data as $key => $_) {
            if ($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }
}
