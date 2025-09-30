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
            ->push('max:' . config('media-library.max_file_size'));
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
