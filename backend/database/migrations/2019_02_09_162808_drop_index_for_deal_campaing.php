<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropIndexForDealCampaing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
        \Schema::table('deal_campaign_agents', function (Blueprint $blueprint) {
            $blueprint->dropForeign('fk_deal_campaign_agents_2');
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
