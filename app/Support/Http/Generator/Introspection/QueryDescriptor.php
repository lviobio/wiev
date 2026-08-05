<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

/**
 * Everything a listing query exposes to clients.
 */
final readonly class QueryDescriptor
{
    /**
     * @param  list<string>  $sorts
     * @param  list<FilterIntrospection>  $filters
     * @param  list<int>  $allowedPerPage
     */
    public function __construct(
        public array $sorts,
        public ?string $defaultSort,
        public array $filters,
        public array $allowedPerPage,
    ) {
    }
}
