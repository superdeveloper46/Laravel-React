<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgencyCompanyIdToDealCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deal_campaigns', function (Blueprint $table) {
            $table->integer('agency_company_id')->unsigned()->nullable()->after('id');
            $table->foreign('agency_company_id')->references('id')->on('agency_companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deal_campaigns', function (Blueprint $table) {
            $table->dropColumn('agency_company_id');
        });
    }
}
