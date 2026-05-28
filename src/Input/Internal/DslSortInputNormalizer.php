<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * sort 输入归一化器。
 *
 * 仅接受标准 sort item list；不负责字段白名单校验。
 *
 * @internal
 */
class DslSortInputNormalizer
{
    private DslJsonDecoder $jsonDecoder;

    public function __construct(?DslJsonDecoder $jsonDecoder = null)
    {
        $this->jsonDecoder = $jsonDecoder ?? new DslJsonDecoder();
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->jsonDecoder->tryDecode($value, $decoded)) {
            $value = $decoded;
        }

        if (is_array($value) && $this->jsonDecoder->isListArray($value)) {
            return $value;
        }

        throw DslQueryDslException::invalidSectionFormat('sort');
    }
}
