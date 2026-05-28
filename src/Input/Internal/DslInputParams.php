<?php

namespace HongXunPan\EloquentQueryDsl\Input\Internal;

/**
 * 外部输入参数读取对象。
 *
 * 该对象集中承接数组 key 读取细节，让上层 parser / mapper 只表达输入协议语义。
 *
 * @internal
 */
class DslInputParams
{
    /**
     * @param array<string, mixed> $params
     */
    private function __construct(private array $params)
    {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        return new self($params);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    public function get(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function prefixed(string $prefix): array
    {
        $values = [];
        foreach ($this->params as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, $prefix)) {
                continue;
            }

            $field = trim(substr($key, strlen($prefix)));
            if ($field !== '') {
                $values[$field] = $value;
            }
        }

        return $values;
    }
}
