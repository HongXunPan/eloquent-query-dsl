<?php

declare(strict_types=1);

namespace HongXunPan\EloquentQueryDsl\Tests\Support;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

final class TestDatabase
{
    public static function boot(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = $capsule->schema();
        $schema->dropIfExists('dsl_core_regression_comments');
        $schema->dropIfExists('dsl_core_regression_comment_authors');
        $schema->dropIfExists('dsl_core_regression_articles');

        $schema->create('dsl_core_regression_articles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('code')->nullable();
            $table->string('status')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        $schema->create('dsl_core_regression_comments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('author_id')->nullable();
            $table->string('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $schema->create('dsl_core_regression_comment_authors', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        Capsule::table('dsl_core_regression_articles')->insert([
            ['id' => 1, 'title' => 'Hello Alumni', 'code' => 'AB001', 'status' => 'published', 'sort_order' => 20, 'published_at' => '2026-05-01 10:00:00'],
            ['id' => 2, 'title' => 'Hello Draft', 'code' => 'AB002', 'status' => 'draft', 'sort_order' => 10, 'published_at' => null],
            ['id' => 3, 'title' => 'Guide', 'code' => 'AC100', 'status' => 'published', 'sort_order' => 30, 'published_at' => '2026-05-02 10:00:00'],
        ]);

        Capsule::table('dsl_core_regression_comments')->insert([
            ['id' => 1, 'article_id' => 1, 'author_id' => 1, 'body' => 'first comment', 'created_at' => '2026-05-01 11:00:00'],
            ['id' => 2, 'article_id' => 2, 'author_id' => 2, 'body' => 'second note', 'created_at' => '2026-05-01 12:00:00'],
        ]);

        Capsule::table('dsl_core_regression_comment_authors')->insert([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);
    }
}
