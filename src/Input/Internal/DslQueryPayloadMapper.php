<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Input\DslInputMap;

/**
 * query payload 映射器。
 *
 * 把外部输入参数映射为标准 search / filter / between / sort section payload。
 *
 * @internal
 */
class DslQueryPayloadMapper
{
    private const SECTION_SEARCH = 'search';
    private const SECTION_FILTER = 'filter';
    private const SECTION_BETWEEN = 'between';
    private const SECTION_SORT = 'sort';

    public function __construct(
        private ?DslJsonDecoder $jsonDecoder = null,
        private ?DslSortInputNormalizer $sortInputNormalizer = null
    ) {
        $this->jsonDecoder ??= new DslJsonDecoder();
        $this->sortInputNormalizer ??= new DslSortInputNormalizer($this->jsonDecoder);
    }

    /**
     * @return array<string, mixed>
     */
    public function map(DslInputParams $params, DslInputMap $inputMap): array
    {
        if ($inputMap->isFlatInput()) {
            return $this->flatPayload($params, $inputMap);
        }

        $queryKey = $inputMap->queryKey();
        if ($queryKey !== null && $params->has($queryKey)) {
            return $this->wrappedPayload(
                $this->jsonDecoder->map($params->get($queryKey), true),
                $inputMap
            );
        }

        return $this->topLevelPayload($params, $inputMap);
    }

    /**
     * @return array<string, mixed>
     */
    private function topLevelPayload(DslInputParams $params, DslInputMap $inputMap): array
    {
        $payload = [];
        $this->copySection($payload, $params, $inputMap->searchKey(), self::SECTION_SEARCH);
        $this->copySection($payload, $params, $inputMap->filterKey(), self::SECTION_FILTER);
        $this->copySection($payload, $params, $inputMap->betweenKey(), self::SECTION_BETWEEN);
        $this->copySection($payload, $params, $inputMap->sortKey(), self::SECTION_SORT);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function flatPayload(DslInputParams $params, DslInputMap $inputMap): array
    {
        $payload = [];

        if ($params->has($inputMap->searchKey())) {
            $payload[self::SECTION_SEARCH] = [
                $inputMap->searchKey() => $params->get($inputMap->searchKey()),
            ];
        }

        $filterPrefix = $inputMap->filterPrefixValue();
        if ($filterPrefix !== null) {
            $filters = $params->prefixed($filterPrefix);
            if ($filters !== []) {
                $payload[self::SECTION_FILTER] = $filters;
            }
        } elseif ($params->has($inputMap->filterKey())) {
            $payload[self::SECTION_FILTER] = $this->fieldMap(
                self::SECTION_FILTER,
                $params->get($inputMap->filterKey())
            );
        }

        if ($params->has($inputMap->betweenKey())) {
            $payload[self::SECTION_BETWEEN] = $this->fieldMap(
                self::SECTION_BETWEEN,
                $params->get($inputMap->betweenKey())
            );
        }

        if ($params->has($inputMap->sortKey())) {
            $payload[self::SECTION_SORT] = $this->sortInputNormalizer->normalize(
                $params->get($inputMap->sortKey())
            );
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function wrappedPayload(array $payload, DslInputMap $inputMap): array
    {
        $this->moveSection($payload, $inputMap->searchKey(), self::SECTION_SEARCH);
        $this->moveSection($payload, $inputMap->filterKey(), self::SECTION_FILTER);
        $this->moveSection($payload, $inputMap->betweenKey(), self::SECTION_BETWEEN);
        $this->moveSection($payload, $inputMap->sortKey(), self::SECTION_SORT);

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function copySection(array &$payload, DslInputParams $params, string $externalKey, string $sectionName): void
    {
        if (!$params->has($externalKey)) {
            return;
        }

        $this->putSection(
            $payload,
            $sectionName,
            $this->sectionValue($sectionName, $params->get($externalKey))
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function moveSection(array &$payload, string $externalKey, string $sectionName): void
    {
        if (!$this->hasPayloadKey($payload, $externalKey)) {
            return;
        }

        $value = $this->sectionValue($sectionName, $payload[$externalKey]);
        if ($externalKey === $sectionName) {
            $payload[$sectionName] = $value;
            return;
        }

        unset($payload[$externalKey]);
        $this->putSection($payload, $sectionName, $value);
    }

    private function sectionValue(string $sectionName, mixed $value): mixed
    {
        return match ($sectionName) {
            self::SECTION_SORT => $this->sortInputNormalizer->normalize($value),
            self::SECTION_SEARCH, self::SECTION_FILTER, self::SECTION_BETWEEN => $this->fieldMap($sectionName, $value),
            default => $value,
        };
    }

    private function fieldMap(string $sectionName, mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || !$this->jsonDecoder->tryDecode($value, $decoded)) {
            throw DslQueryDslException::invalidSectionFormat($sectionName);
        }

        if (!is_array($decoded) || $this->jsonDecoder->isListArray($decoded)) {
            throw DslQueryDslException::invalidSectionFormat($sectionName);
        }

        return $this->jsonDecoder->assertMap($decoded, true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function putSection(array &$payload, string $sectionName, mixed $value): void
    {
        if ($this->hasPayloadKey($payload, $sectionName)) {
            throw DslQueryDslException::invalidQueryFormat();
        }

        $payload[$sectionName] = $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasPayloadKey(array $payload, string $key): bool
    {
        return array_key_exists($key, $payload);
    }
}
