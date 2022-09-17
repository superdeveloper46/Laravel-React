<?php

use Illuminate\Database\Seeder;

class TimezonesSeed extends Seeder
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
            \App\Models\User::query()->truncate();
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
        }
        $f = fopen(__DIR__ . '/resources/timezones.csv', 'r');
        while (($record = fgetcsv($f)) !== false) {
           list($name, $offset, $offsetDST) = $record;
           if ($name === 'timezone') {
               continue;
           }

           \App\Models\Timezones::create([
               'name' => $name,
               'offset' => (int)$offset,
               'offset_dst' => (int)$offsetDST
           ]);
        }
    }
}
