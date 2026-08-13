<?php

namespace Database\Seeders;

use App\Enums\GuideStatus;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@flexcube.local')->first();

        $lending = Category::create(['name' => 'Lending', 'slug' => 'lending', 'order' => 1]);
        Category::create(['name' => 'Loan origination', 'slug' => 'loan-origination', 'parent_id' => $lending->id, 'order' => 1]);

        $deposits = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 2]);

        $guide = Guide::create([
            'category_id' => $deposits->id,
            'title' => 'Opening a fixed deposit account',
            'slug' => 'opening-a-fixed-deposit-account',
            'description' => 'Step-by-step walkthrough of the FlexCube STDCUSAC screen for new fixed deposits.',
            'language' => 'en',
            'status' => GuideStatus::Published,
            'created_by' => $admin?->id,
            'updated_by' => $admin?->id,
        ]);

        Step::create([
            'guide_id' => $guide->id,
            'order' => 1,
            'title' => 'Open the customer account screen',
            'body' => '<p>Navigate to <strong>STDCUSAC</strong> from the main menu.</p>',
        ]);
    }
}
