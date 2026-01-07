<?php

namespace Database\Seeders;

// use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // $this->call(UserSeeder::class);
        UserFactory::new()->count(10)->create();
    }
}
