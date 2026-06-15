<?php

namespace HongXunPan\EloquentQueryDsl\Field;

use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslDefinitionException;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;

/**
 * DSL 安全标识符校验器。
 *
 * 普通字段、实体别名与 relation path 的每一段都必须是安全 identifier；
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
                'query dsl ' . $label . '格式错误：' . $identifier,
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

    public static function relationPathForDefinition(string $relationPath): string
    {
        $relationPath = trim($relationPath);
        if ($relationPath === '') {
            throw DslQueryDslDefinitionException::fromMessage('query dsl relation格式错误：' . $relationPath);
        }

        $segments = explode('.', $relationPath);
        foreach ($segments as $segment) {
            if (!self::isValid($segment)) {
                throw DslQueryDslDefinitionException::fromMessage(
                    'query dsl relation格式错误：' . $relationPath,
                );
            }
        }

        return $relationPath;
    }

    public static function isValid(string $identifier): bool
    {
        return preg_match(self::PATTERN, $identifier) === 1;
    }
}
