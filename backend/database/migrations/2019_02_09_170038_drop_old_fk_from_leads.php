<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropOldFkFromLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
        \Schema::table('leads', function (Blueprint $blueprint) {
            $blueprint->dropForeign('fk_leads_1');
        });
        DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
