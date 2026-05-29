<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * DSL 输入中的 JSON 解码器。
 *
 * 只以 json_decode 的真实结果判断 JSON，不通过字符串前缀 / 后缀猜测。
 *
 * @internal
 */
class DslJsonDecoder
{
    public function isMapInput(mixed $raw): bool
    {
        if (is_array($raw)) {
            return true;
        }

        if (!$this->tryDecode($raw, $decoded)) {
            return false;
        }

        return is_array($decoded) && !$this->isListArray($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    public function map(mixed $raw, bool $queryError): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $this->assertMap($raw, $queryError);
        }

        if (!$this->tryDecode($raw, $decoded) || !is_array($decoded) || $this->isListArray($decoded)) {
            throw $queryError
                ? DslQueryDslException::invalidQueryFormat()
                : DslQueryDslException::invalidPageFormat();
        }

        return $this->assertMap($decoded, $queryError);
    }

    public function tryDecode(mixed $raw, mixed &$decoded): bool
    {
        if (!is_string($raw)) {
            return false;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return false;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param array<mixed, mixed> $data
     * @return array<string, mixed>
     */
    public function assertMap(array $data, bool $queryError): array
    {
        $map = [];
        foreach ($data as $key => $_) {
            if (!is_string($key) || trim($key) === '') {
                throw $queryError
                    ? DslQueryDslException::invalidQueryFormat()
                    : DslQueryDslException::invalidPageFormat();
            }

            $map[$key] = $data[$key];
        }

        return $map;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function isListArray(array $data): bool
    {
        if ($data === []) {
            return false;
        }

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
