<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // SyndifyDemoSeeder::class, // 🟢 N-runniw ghir l-Base
           SyndifyTestDataSeeder::class,
            // SyndifyDocumentSeeder::class, // 🔴 Hadi d-derna liha Commentaire
        ]);
    }
}
