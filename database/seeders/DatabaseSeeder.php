<?php

namespace Database\Seeders;

use App\Models\Translation;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Translation::factory()
            ->count(100_000)
            ->create()
            ->each(function ($translation) {
                $tag = \App\Models\Tag::firstOrCreate(['name' => fake()->randomElement(['mobile', 'web', 'desktop'])]);
                $translation->tags()->attach($tag->id);
            });
    }
}
