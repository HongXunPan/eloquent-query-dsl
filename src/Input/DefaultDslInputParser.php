<?php

namespace HongXunPan\EloquentQueryDsl\Input;

use HongXunPan\EloquentQueryDsl\Input\Contract\DslInputParser;
use HongXunPan\EloquentQueryDsl\Input\Internal\DslInputParams;
use HongXunPan\EloquentQueryDsl\Input\Internal\DslPagePayloadMapper;
use HongXunPan\EloquentQueryDsl\Input\Internal\DslQueryPayloadMapper;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;

/**
 * 默认 DSL 输入解析器。
 *
 * 支持默认 query 包裹协议、自定义顶层参数名、JSON object / array 输入，
 * 以及轻量 flat 输入。它不读取 definition，也不判断字段是否开放。
 */
class DefaultDslInputParser implements DslInputParser
{
    public function __construct(
        private ?DslQueryPayloadMapper $queryPayloadMapper = null,
        private ?DslPagePayloadMapper $pagePayloadMapper = null
    ) {
        $this->queryPayloadMapper ??= new DslQueryPayloadMapper();
        $this->pagePayloadMapper ??= new DslPagePayloadMapper();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function queryInput(array $params, DslInputMap $inputMap): DslQueryInput
    {
        return DslQueryInput::fromRaw(
            $this->queryPayloadMapper->map(
                DslInputParams::fromArray($params),
                $inputMap
            )
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public function pageInput(array $params, DslInputMap $inputMap): DslPageInput
    {
        return $this->pagePayloadMapper->toPageInput(
            DslInputParams::fromArray($params),
            $inputMap
        );
    }
}
