<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // $this->call(AdminSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(AppConfigSeeder::class);
        $this->call(GhanaConstituencyAndDistrictsSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(ProgrammeTagSeeder::class);
        $this->call(UpdateCourseNamesSeeder::class);
        $this->call(PartnerSeeder::class);

        // Opt-in realistic lifecycle seed for QA/UAT datasets.
        if ((bool) env('SEED_REALISTIC_LIFECYCLE', false)) {
            $this->call(RealisticStudentLifecycleSeeder::class);
        }
    }
}
