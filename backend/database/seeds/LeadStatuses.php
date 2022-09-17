<?php

use Illuminate\Database\Seeder;

class LeadStatuses extends Seeder
{
   private $statuses = [
        'NONE',
        'NEW',
        'VIEWED',
        'CONTACTED_SMS',
        'CONTACTED_CALL',
        'CONTACTED_EMAIL',
        'MISSED',
        'BAD',
        'SOLD',
        'SMS_REPLY'
    ];
    
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

        foreach ($this->statuses as $status) {
            \App\Models\LeadStatus::create([
                'name' => $status,
                'description' => $status,
                'type' => $status,
            ]);
        }
    }
}
