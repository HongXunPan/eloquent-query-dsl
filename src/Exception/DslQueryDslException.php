<?php

namespace HongXunPan\EloquentQueryDsl\Exception;

use InvalidArgumentException;

/**
 * Query DSL 输入期异常。
 *
 * 该异常只承接用户输入导致的 DSL 错误，不直接承担定义期或运行期异常职责。
 * 项目边界可按需要把该异常转换为项目统一业务异常。
 */
class DslQueryDslException extends InvalidArgumentException
{
    public static function invalidQueryFormat(): self
    {
        return new self('query格式错误');
    }

    public static function invalidQuery(string $message): self
    {
        return new self($message);
    }

    public static function emptyQuerySectionName(): self
    {
        return new self('query section名称不能为空');
    }

    public static function invalidPageFormat(): self
    {
        return new self('page格式错误');
    }

    public static function invalidSectionFormat(string $sectionName): self
    {
        return new self('query.' . $sectionName . '格式错误');
    }

    public static function invalidFieldFormat(): self
    {
        return new self('query字段格式错误');
    }

    public static function disabledSection(string $sectionName): self
    {
        return new self('当前查询未开放 ' . $sectionName . ' 能力');
    }

    public static function unknownField(string $sectionName, string $field): self
    {
        return new self('query.' . $sectionName . ' 未开放字段：' . $field);
    }

    public static function invalidField(string $sectionName, string $field, string $message): self
    {
        return new self('query.' . $sectionName . '.' . $field . ' ' . $message);
    }
}
