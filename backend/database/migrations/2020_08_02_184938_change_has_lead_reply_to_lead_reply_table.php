<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeHasLeadReplyToLeadReplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            "ALTER TABLE deal_actions CHANGE has_lead_reply lead_reply_type ENUM('NONE', 'SMS_REPLY', 'SMS_REPLY_CONTAIN', 'MAIL_OPEN') default 'NONE'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_reply', function (Blueprint $table) {
            //
        });
    }
}
