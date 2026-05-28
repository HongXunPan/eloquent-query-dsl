<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;
use HongXunPan\EloquentQueryDsl\Page\DslPageInput;

/**
 * page payload 映射器。
 *
 * 把外部 page / limit / export_limit 输入映射为标准分页输入对象。
 *
 * @internal
 */
class DslPagePayloadMapper
{
    private DslJsonDecoder $jsonDecoder;

    public function __construct(?DslJsonDecoder $jsonDecoder = null)
    {
        $this->jsonDecoder = $jsonDecoder ?? new DslJsonDecoder();
    }

    public function toPageInput(DslInputParams $params, DslInputMap $inputMap): DslPageInput
    {
        $pagePayload = [];
        $exportLimit = $params->get($inputMap->exportLimitKey());

        if ($params->has($inputMap->pageKey())) {
            $rawPage = $params->get($inputMap->pageKey());
            $pagePayload = $this->jsonDecoder->isMapInput($rawPage)
                ? $this->remapPagePayload($this->jsonDecoder->map($rawPage, false), $inputMap)
                : ['page' => $rawPage];
        }

        if ($params->has($inputMap->limitKey())) {
            $this->putPageValue($pagePayload, 'limit', $params->get($inputMap->limitKey()));
        }

        if ($exportLimit === null && $this->hasPayloadKey($pagePayload, $inputMap->exportLimitKey())) {
            $exportLimit = $pagePayload[$inputMap->exportLimitKey()];
            unset($pagePayload[$inputMap->exportLimitKey()]);
        }

        return DslPageInput::fromRaw($pagePayload, $exportLimit);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function remapPagePayload(array $payload, DslInputMap $inputMap): array
    {
        $this->movePageValue($payload, $inputMap->pageKey(), 'page');
        $this->movePageValue($payload, $inputMap->limitKey(), 'limit');

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function movePageValue(array &$payload, string $externalKey, string $pageKey): void
    {
        if (!$this->hasPayloadKey($payload, $externalKey)) {
            return;
        }

        $value = $payload[$externalKey];
        if ($externalKey === $pageKey) {
            $payload[$pageKey] = $value;
            return;
        }

        unset($payload[$externalKey]);
        $this->putPageValue($payload, $pageKey, $value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function putPageValue(array &$payload, string $key, mixed $value): void
    {
        if ($this->hasPayloadKey($payload, $key)) {
            throw DslQueryDslException::invalidPageFormat();
        }

        $payload[$key] = $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasPayloadKey(array $payload, string $key): bool
    {
        return array_key_exists($key, $payload);
    }
}
