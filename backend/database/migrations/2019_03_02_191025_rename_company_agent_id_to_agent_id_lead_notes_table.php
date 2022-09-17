<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameCompanyAgentIdToAgentIdLeadNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::table('lead_notes', function (Blueprint $blueprint) {
            $blueprint->renameColumn('company_agent_id', 'agent_id');
            $blueprint->foreign('agent_id')->references('id')->on('users');
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_notes', function (Blueprint $table) {
            //
        });
    }
}
