<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddLeadDealCampaignIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
        Schema::table('leads', function (Blueprint $blueprint) {
            $blueprint->renameColumn('deal_campaign_integration_id', 'deal_campaign_id');
            $blueprint->foreign('deal_campaign_id')->references('id')->on('deal_campaigns');
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
