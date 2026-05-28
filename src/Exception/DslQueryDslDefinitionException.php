<?php

namespace HongXunPan\EloquentQueryDsl\Exception;

use LogicException;

/**
 * Query DSL 定义期异常。
 *
 * 该异常用于承接服务端 DSL 声明、内核配置与稳定内部约束错误，
 * 与用户输入导致的 `DslQueryDslException` 明确分层。
 */
class DslQueryDslDefinitionException extends LogicException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
