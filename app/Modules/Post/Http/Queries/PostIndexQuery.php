<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Queries;

use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Support\Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Support\Spatie\QueryBuilder\QueryBuilder;

class PostIndexQuery extends QueryBuilder
{
    public function __construct()
    {
        $query = Post::query()->with(PostResource::eagerLoads());

        parent::__construct($query);

        $this
            ->defaultSort('id')
            ->allowedSorts(
                AllowedSort::field('id'),
                AllowedSort::field('title'),
                AllowedSort::field('created_at'),
            )
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::trashed(),
                AllowedFilter::search(['title', 'content']),
                AllowedFilter::dateRange('created_at'),
            );
    }
}
