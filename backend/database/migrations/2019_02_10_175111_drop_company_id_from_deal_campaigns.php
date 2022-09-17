<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropCompanyIdFromDealCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deal_campaigns', function (Blueprint $table) {
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
            $table->dropForeign('deal_campaigns_company_id_foreign');
            $table->dropColumn('company_id');
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
        //
    }
}
