<?php

declare(strict_types=1);

/**
 * 轻量代码风格检查脚本。
 *
 * 当前脚本只检查不依赖外部工具的基础仓库卫生：
 * - 文本文件必须使用 LF；
 * - 文件必须以换行结尾；
 * - PHP / JSON / YAML 文件不允许行尾空白；
 * - PHP 文件不使用制表符缩进。
 */
final class CodeStyleCheck
{
    private const ROOT = __DIR__ . '/..';

    /**
     * @var string[]
     */
    private array $errors = [];

    public function run(): int
    {
        foreach ($this->targetFiles() as $file) {
            $this->checkFile($file);
        }

        if ($this->errors === []) {
            echo "代码风格检查通过\n";
            return 0;
        }

        foreach ($this->errors as $error) {
            fwrite(STDERR, $error . PHP_EOL);
        }

        return 1;
    }

    /**
     * @return string[]
     */
    private function targetFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::ROOT, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if ($this->shouldSkip($path) || !$this->isTextTarget($path)) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    private function shouldSkip(string $path): bool
    {
        $relative = $this->relativePath($path);

        foreach (['.git/', '.idea/', 'vendor/'] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return in_array($relative, ['composer.lock'], true);
    }

    private function isTextTarget(string $path): bool
    {
        $relative = $this->relativePath($path);
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        return in_array($extension, ['php', 'json', 'md', 'yml', 'yaml', 'dist'], true)
            || in_array(basename($relative), ['.editorconfig', '.gitattributes', '.gitignore', 'LICENSE'], true);
    }

    private function checkFile(string $path): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            $this->errors[] = $this->relativePath($path) . ' 无法读取';
            return;
        }

        $relative = $this->relativePath($path);

        if (str_contains($content, "\r\n") || str_contains($content, "\r")) {
            $this->errors[] = $relative . ' 必须使用 LF 换行';
        }

        if ($content !== '' && !str_ends_with($content, "\n")) {
            $this->errors[] = $relative . ' 必须以换行结尾';
        }

        $this->checkTrailingWhitespace($relative, $content);
        $this->checkPhpTabs($relative, $content);
    }

    private function checkTrailingWhitespace(string $relative, string $content): void
    {
        if (str_ends_with($relative, '.md')) {
            return;
        }

        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if ($line !== rtrim($line, " \t")) {
                $this->errors[] = sprintf('%s:%d 存在行尾空白', $relative, $index + 1);
            }
        }
    }

    private function checkPhpTabs(string $relative, string $content): void
    {
        if (!str_ends_with($relative, '.php')) {
            return;
        }

        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, "\t")) {
                $this->errors[] = sprintf('%s:%d PHP 文件不使用制表符缩进', $relative, $index + 1);
            }
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(self::ROOT, '', $path), DIRECTORY_SEPARATOR);
    }
}

exit((new CodeStyleCheck())->run());

