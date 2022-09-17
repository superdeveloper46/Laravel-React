<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgentAgencyIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
            $table->integer('agent_agency_id')->unsigned()->nullable()->after('id');
            $table->foreign('agent_agency_id')->references('id')->on('users');
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
