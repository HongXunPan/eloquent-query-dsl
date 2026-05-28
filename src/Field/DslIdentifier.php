<?php

namespace HongXunPan\EloquentQueryDsl\Field;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * DSL 安全标识符校验器。
 *
 * 普通字段、实体别名与 relation 方法名都必须是安全 identifier；
 * raw SQL / 表达式类能力后续如需支持，应另开显式 API，不能复用普通字段字符串。
 *
 * @internal
 */
final class DslIdentifier
{
    private const PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public static function forDefinition(string $identifier, string $label): string
    {
        $identifier = trim($identifier);
        if (!self::isValid($identifier)) {
            throw DslQueryDslDefinitionException::fromMessage(
                'query dsl ' . $label . '格式错误：' . $identifier
            );
        }

        return $identifier;
    }

    public static function forInput(string $identifier): string
    {
        $identifier = trim($identifier);
        if (!self::isValid($identifier)) {
            throw DslQueryDslException::invalidFieldFormat();
        }

        return $identifier;
    }

    public static function isValid(string $identifier): bool
    {
        return preg_match(self::PATTERN, $identifier) === 1;
    }
}
