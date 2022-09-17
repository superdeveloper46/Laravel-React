<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgentLeadCountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deal_campaign_agents', function (Blueprint $blueprint) {
           $blueprint->integer('agent_leads_count')->nullable()->default(0)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    
        Schema::table('deal_campaign_agents', function (Blueprint $blueprint) {
            $blueprint->dropColumn('agent_leads_count');
        });
    }
}
