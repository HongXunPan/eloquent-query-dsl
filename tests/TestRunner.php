<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Package;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require __DIR__ . '/../src/Package.php';
}

$assertions = [
    '包名常量正确' => Package::name() === 'hongxunpan/eloquent-query-dsl',
    '包标识类可加载' => class_exists(Package::class),
];

foreach ($assertions as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "[失败] {$message}\n");
        exit(1);
    }

    fwrite(STDOUT, "[通过] {$message}\n");
}

fwrite(STDOUT, "Eloquent Query DSL skeleton 测试通过。\n");

