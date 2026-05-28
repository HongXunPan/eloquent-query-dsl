<?php

namespace HongXunPan\EloquentQueryDsl;

/**
 * Eloquent Query DSL 包标识。
 *
 * 当前类用于暴露包名常量，并为最小 autoload / 安装探针提供稳定入口。
 */
final class Package
{
    public const NAME = 'hongxunpan/eloquent-query-dsl';

    public static function name(): string
    {
        return self::NAME;
    }
}
