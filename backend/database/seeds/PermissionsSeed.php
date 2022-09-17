<?php

use Illuminate\Database\Seeder;

class PermissionsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('ENV_NAME') === 'development') {
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\Permission::query()->truncate();
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
        }

        foreach (\App\Models\Permission::getAll() as $permission) {
            \App\Models\Permission::create([
                'name' => $permission,
                'description' => $permission,
            ]);
        }
    }
}
