<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldStopOnManualContactDealActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deal_actions', function (Blueprint $blueprint) {
            $blueprint->boolean('stop_on_manual_contact')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deal_actions', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['stop_on_manual_contact']);
        });
    }
}
