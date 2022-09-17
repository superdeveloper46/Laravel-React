<?php

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
        $this->call(PermissionsSeed::class);
        $this->call(LeadStatuses::class);
        $this->call(TimezonesSeed::class);
//        if (env('ENV_NAME') === 'development') {
//            $this->call(UsersDevSeed::class);
//        } else {
            $this->call(User::class);
//        }
    }
}
