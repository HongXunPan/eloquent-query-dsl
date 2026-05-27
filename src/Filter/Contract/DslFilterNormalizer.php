<?php

namespace HongXunPan\EloquentQueryDsl\Filter\Contract;

use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

/**
 * Query DSL filter 归一化契约。
 *
 * 共享包只关心“按规则把 filter payload 归一化为可查询值集合”，
 * 不关心具体 validator、异常翻译或业务项目适配方式。
 */
interface DslFilterNormalizer
{
    /**
     * @param array<string, mixed> $payload 原始 filter payload
     * @param array<string, string> $rules 以 payload field 为 key 的规则集合
     * @param array<string, mixed> $options 归一化实现可选配置
     */
    public function normalize(array $payload, array $rules, array $options = array()): DslNormalizedFilterSet;
}

