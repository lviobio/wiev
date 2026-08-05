<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Render;

/**
 * A file the generator wants on disk, and what it should contain.
 */
final readonly class GeneratedFile
{
    public function __construct(
        public string $path,
        public string $contents,
    ) {
    }

    public function isUpToDate(): bool
    {
        return is_file($this->path) && file_get_contents($this->path) === $this->contents;
    }

    public function relativePath(): string
    {
        return str_replace(base_path() . '/', '', $this->path);
    }
}
