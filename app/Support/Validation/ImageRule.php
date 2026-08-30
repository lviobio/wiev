<?php
declare(strict_types=1);

namespace App\Support\Validation;

class ImageRule
{
    private array $set = [];

    public static function make(): static
    {
        return new self()
            ->push('image')
            // правило max: считает килобайты, конфиг media library — байты
            ->push('max:' . intdiv((int) config('media-library.max_file_size'), 1024));
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
