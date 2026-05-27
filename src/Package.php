<?php

namespace HongXunPan\EloquentQueryDsl;

/**
 * Eloquent Query DSL 包骨架标识。
 *
 * 当前类只用于 skeleton 阶段确认命名空间与 autoload 可用；
 * 真正的 QueryDSL core 会在后续批次按 shared stable 边界逐步平移。
 */
final class Package
{
    public const NAME = 'hongxunpan/eloquent-query-dsl';

    public static function name(): string
    {
        return self::NAME;
    }
}

