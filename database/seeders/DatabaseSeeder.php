<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@flexcube.local',
        ]);
        $admin->assignRole(RoleEnum::Admin->value);

        $contributor = User::factory()->create([
            'name' => 'Contributor',
            'email' => 'contributor@flexcube.local',
        ]);
        $contributor->assignRole(RoleEnum::Contributor->value);

        $this->call(SampleContentSeeder::class);
    }
}
