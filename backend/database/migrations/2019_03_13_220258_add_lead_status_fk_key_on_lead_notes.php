<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLeadStatusFkKeyOnLeadNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::table('lead_notes', function (Blueprint $blueprint) {
            $blueprint->integer('lead_status_id')->nullable()->unsigned()->change();
            $blueprint->foreign('lead_status_id')->references('id')->on('lead_statuses');
        });
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
