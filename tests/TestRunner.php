<?php

declare(strict_types=1);

use HongXunPan\EloquentQueryDsl\Package;
use HongXunPan\EloquentQueryDsl\Condition\DslFilterCondition;
use HongXunPan\EloquentQueryDsl\Condition\DslSortCondition;
use HongXunPan\EloquentQueryDsl\Exception\DslQueryDslException;
use HongXunPan\EloquentQueryDsl\Field\DslFieldPath;
use HongXunPan\EloquentQueryDsl\Filter\Contract\DslFilterNormalizer;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterItem;
use HongXunPan\EloquentQueryDsl\Filter\Value\DslNormalizedFilterSet;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require __DIR__ . '/../src/Package.php';
    require __DIR__ . '/../src/Exception/DslQueryDslDefinitionException.php';
    require __DIR__ . '/../src/Exception/DslQueryDslException.php';
    require __DIR__ . '/../src/Exception/DslQueryDslRuntimeException.php';
    require __DIR__ . '/../src/Field/DslFieldPath.php';
    require __DIR__ . '/../src/Condition/DslCondition.php';
    require __DIR__ . '/../src/Condition/DslFieldCondition.php';
    require __DIR__ . '/../src/Condition/DslFilterCondition.php';
    require __DIR__ . '/../src/Condition/DslSearchCondition.php';
    require __DIR__ . '/../src/Condition/DslBetweenCondition.php';
    require __DIR__ . '/../src/Condition/DslSortCondition.php';
    require __DIR__ . '/../src/Filter/Value/DslNormalizedFilterItem.php';
    require __DIR__ . '/../src/Filter/Value/DslNormalizedFilterSet.php';
    require __DIR__ . '/../src/Filter/Contract/DslFilterNormalizer.php';
}

$fieldPath = DslFieldPath::fromDefinition('status', 'activity');
$relationFieldPath = DslFieldPath::fromInput('alumni_card.real_name', 'activity');
$filterCondition = DslFilterCondition::fromField($fieldPath, 'published');
$sortCondition = DslSortCondition::make($fieldPath, 'DESC');
$filterItem = new DslNormalizedFilterItem('status', ['published']);
$filterSet = DslNormalizedFilterSet::success(
    ['status' => 'published'],
    ['status' => $filterItem]
);
$failureSet = DslNormalizedFilterSet::failure(['query.filter 参数错误']);

$assertions = [
    '包名常量正确' => Package::name() === 'hongxunpan/eloquent-query-dsl',
    '包标识类可加载' => class_exists(Package::class),
    '输入期异常类可加载' => class_exists(DslQueryDslException::class),
    '字段路径可解析主实体字段' => $fieldPath->canonical() === 'activity.status' && $fieldPath->isMainEntity(),
    '字段路径可解析 relation 字段' => $relationFieldPath->canonical() === 'alumni_card.real_name' && $relationFieldPath->isRelation(),
    'filter condition 保留 section 与值' => $filterCondition->sectionName() === 'filter' && $filterCondition->value() === 'published',
    'sort condition 归一化排序方向' => $sortCondition->order() === DslSortCondition::ORDER_DESC,
    'filter normalizer 契约可加载' => interface_exists(DslFilterNormalizer::class),
    'filter item 保留 payload 字段' => $filterItem->payloadField() === 'status',
    'filter item 保留归一化值列表' => $filterItem->normalizedValues() === ['published'],
    'filter set 可按字段读取归一化结果' => $filterSet->item('status')?->normalizedValues() === ['published'],
    'filter set 空字段不返回 item' => $filterSet->item('') === null,
    'filter failure set 保留错误信息' => $failureSet->isFailed() && $failureSet->errors() === ['query.filter 参数错误'],
];

foreach ($assertions as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "[失败] {$message}\n");
        exit(1);
    }

    fwrite(STDOUT, "[通过] {$message}\n");
}

fwrite(STDOUT, "Eloquent Query DSL skeleton 测试通过。\n");
