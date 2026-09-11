<?php

namespace Database\Seeders;

use App\Enums\AuthAbilityEnum;
use App\Models\User;
use App\Modules\Post\Models\Post;
use Bouncer;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->seedAuthorization();

        tap(User::factory()->create([
            'name' => 'Super Administrator User',
            'email' => 'test-1@example.com',
        ]), function (User $user) {
            $user->assign('superadmin');
        });

        tap(User::factory()->create([
            'name' => 'Administrator User',
            'email' => 'test-2@example.com',
        ]), function (User $user) {
            $user->assign('admin');
        });

        tap(User::factory()->create([
            'name' => 'User',
            'email' => 'test-3@example.com',
        ]), function (User $user) {
            $user->assign('user');
        });

        User::factory()->create([
            'name' => 'User without roles',
            'email' => 'test-4@example.com',
        ]);

        Post::factory()->count(100)->create();
    }

    private function seedAuthorization(): void
    {
        $superadmin = Bouncer::role()->firstOrCreate([
            'name' => 'superadmin',
            'title' => 'Super Administrator',
        ]);

        $admin = Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrator',
        ]);

        $user = Bouncer::role()->firstOrCreate([
            'name' => 'user',
            'title' => 'User',
        ]);

        Bouncer::allow($superadmin)->everything();

        Bouncer::allow($admin)->everything();
        Bouncer::forbid($admin)->toManage(User::class);

        Bouncer::allow($user)->to(AuthAbilityEnum::Access->value, Post::class);
    }
}
