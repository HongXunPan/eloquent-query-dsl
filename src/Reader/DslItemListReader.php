<?php

namespace HongXunPan\EloquentQueryDsl\Reader;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;

/**
 * Query DSL 列表型 section 读取器。
 *
 * 只负责读取形如 sort 的列表型输入；
 * 不理解 item 内部字段，也不执行排序。
 *
 * @internal
 */
class DslItemListReader
{
    protected DslSectionReader $sectionReader;

    public function __construct(?DslSectionReader $sectionReader = null)
    {
        $this->sectionReader = $sectionReader ?? new DslSectionReader();
    }

    /**
     * @return array<int, mixed>|null
     */
    public function read(DslQueryInput $input, string $sectionName): ?array
    {
        $section = $input->get($sectionName);
        if ($section === null) {
            return null;
        }

        $value = $section->value();
        if (!is_array($value) || !$this->sectionReader->isListArray($value)) {
            throw DslQueryDslException::invalidSectionFormat($sectionName);
        }

        return $value;
    }
}
