<?php
declare(strict_types=1);

namespace App\Support\Validation;

class FileRule
{
    private array $set = [];

    /**
     * @param  int|null  $maxBytes  Defaults to `media-library.max_file_size` - pass an
     *                              override for a value with a different limit of its
     *                              own (e.g. `UploadedChunk`, sized to `uploads.chunked.
     *                              chunk_max_size` rather than a whole file's cap).
     */
    public static function make(?int $maxBytes = null): static
    {
        return new self()
            ->push('file')
            // правило max: считает килобайты, конфиг — байты
            ->push('max:' . intdiv($maxBytes ?? (int) config('media-library.max_file_size'), 1024));
    }

    private function push(string $rule): static
    {
        $this->set[] = $rule;

        return $this;
    }

    public function toArray(): array
    {
        return $this->set;
    }
}
