<?php

namespace HongXunPan\EloquentQueryDsl\Input\Contract;

use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Input\DslQueryInput;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;

/**
 * DSL 外部输入解析契约。
 *
 * 实现类只负责把任意外部输入转换成标准 DSL 输入对象，不负责字段能力、
 * validator、查询应用或响应结构。
 */
interface DslInputParser
{
    /**
     * @param array<string, mixed> $params
     */
    public function queryInput(array $params, DslInputMap $inputMap): DslQueryInput;

    /**
     * @param array<string, mixed> $params
     */
    public function pageInput(array $params, DslInputMap $inputMap): DslPageInput;
}
