<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Throwable;

final class Assert
{
    public static function same(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
        }
    }

    public static function true(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    public static function contains(string $needle, string $haystack, string $message): void
    {
        if (!str_contains($haystack, strtolower($needle))) {
            throw new RuntimeException($message . "\nneedle: " . $needle . "\nhaystack: " . $haystack);
        }
    }

    public static function notContains(string $needle, string $haystack, string $message): void
    {
        if (str_contains($haystack, strtolower($needle))) {
            throw new RuntimeException($message . "\nneedle: " . $needle . "\nhaystack: " . $haystack);
        }
    }

    /**
     * @param class-string<Throwable> $expectedException
     */
    public static function throws(callable $callback, string $expectedException, string $messageContains = ''): void
    {
        try {
            $callback();
        } catch (Throwable $throwable) {
            if (!$throwable instanceof $expectedException) {
                throw new RuntimeException('异常类型不符合预期：' . $throwable::class . '，期望：' . $expectedException, 0, $throwable);
            }

            if ($messageContains !== '' && !str_contains($throwable->getMessage(), $messageContains)) {
                throw new RuntimeException('异常消息不符合预期：' . $throwable->getMessage() . '，期望包含：' . $messageContains, 0, $throwable);
            }

            return;
        }

        throw new RuntimeException('预期应抛出异常：' . $expectedException);
    }

    /**
     * @param array<int, int> $expectedIds
     */
    public static function resultIds(Builder $query, array $expectedIds, string $message): void
    {
        $ids = array_map('intval', $query->pluck($query->getModel()->getKeyName())->all());
        self::same($expectedIds, $ids, $message);
    }
}
