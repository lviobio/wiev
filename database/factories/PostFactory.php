<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(2, true),
            'content' => $this->faker->words(2, true),
            'published_at' => Carbon::now(),

            'author_user_id' => User::factory(),
        ];
    }
}
