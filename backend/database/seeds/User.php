<?php

use Illuminate\Database\Seeder;

class User extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTabales();
        
        $agency = \App\Models\Agency::create([
            'email' => 'dmitri.russu@gmail.com',
            'name' => 'Dmitri Russu',
            'max_agency_companies' => 5,
            'subscription_type' => 'BASE',
            'password' => bcrypt('testtest'),
            'role' => \App\Models\User::$ROLE_AGENCY,
        ]);

        $secondAgency = \App\Models\Agency::create([
            'email' => 'costan.alex88@yahoo.ro',
            'name' => 'Alex Agency Costan ',
            'max_agency_companies' => 10,
            'subscription_type' => 'PREMIUM',
            'password' => bcrypt('testtest'),
            'role' => \App\Models\User::$ROLE_AGENCY,
        ]);
        $this->addPermissions($secondAgency);

        $agent = \App\Models\Agent::create([
            'email' => 'dmitri.russu+ag@gmail.com',
            'name' => 'Dmitri Russu',
            'agent_agency_id' => $agency->id,
            'password' => bcrypt('testtest'),
            'role' => \App\Models\User::$ROLE_AGENT,
        ]);
        
        $company =   \App\Models\Company::create([
            'email' => 'dmitri.russu+cp@gmail.com',
            'name' => 'Dmitri Russu',
            'password' => bcrypt('testtest'),
            'role' => \App\Models\User::$ROLE_COMPANY,
        ]);
        
        $agency->companies()->attach($company);
        $company->agents()->attach($agent);
        
        $this->createUsersPermissions($agency, $agent, $company);
    }
    
    /**
     * @param $agency
     * @param $agent
     * @param $company
     */
    public function createUsersPermissions($agency, $agent, $company)
    {
        $users = [$agency, $agent, $company,
            \App\Models\User::create([
                'email' => 'admin.alex@test.com',
                'name' => 'Dmitri Russu',
                'password' => bcrypt('testtest'),
                'role' => \App\Models\User::$ROLE_ADMIN,
            ]),
            \App\Models\User::create([
                'email' => 'admin.dmitri@test.com',
                'name' => 'Dmitri Russu',
                'password' => bcrypt('testtest'),
                'role' => \App\Models\User::$ROLE_ADMIN,
            ]),
            \App\Models\User::create([
                'email' => 'admin@test.com',
                'name' => 'Dmitri Russu',
                'password' => bcrypt('testtest'),
                'role' => \App\Models\User::$ROLE_ADMIN,
            ]),
        ];
        foreach ($users as $user) {
            $this->addPermissions($user);
        }
    }
    
    public function truncateTabales()
    {
        if (env('ENV_NAME') === 'development') {
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\User::query()->truncate();
            DB::getPdo()->query('TRUNCATE TABLE user_permissions');
            DB::getPdo()->query('TRUNCATE TABLE company_agents');
            DB::getPdo()->query('TRUNCATE TABLE agency_companies');
            DB::getPdo()->query('TRUNCATE TABLE deals');
            DB::getPdo()->query('TRUNCATE TABLE deal_campaigns');
            DB::getPdo()->query('TRUNCATE TABLE leads');
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
    
    /**
     * @param $user
     */
    public function addPermissions($user)
    {
        $permissions = \App\Models\Permission::whereIn('name', $user->getDefaultPermissions())->get();
        $user->permissions()->attach($permissions);
    }
}
