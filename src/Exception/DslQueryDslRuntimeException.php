<?php

namespace HongXunPan\EloquentQueryDsl\Exception;

use RuntimeException;

/**
 * Query DSL 运行期异常。
 *
 * 该异常用于承接“已越过定义与输入边界后仍出现”的运行期装配错误，
 * 例如内核注入了错误类型的执行组件。
 */
class DslQueryDslRuntimeException extends RuntimeException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
