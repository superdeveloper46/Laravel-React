<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEnumForLeadStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE lead_statuses MODIFY COLUMN `type` ENUM('NONE', 'NEW', 'VIEWED', 'CONTACTED_SMS', 'CONTACTED_CALL', 'CONTACTED_EMAIL', 'MISSED', 'BAD', 'SOLD', 'SMS_REPLY') NULL DEFAULT 'NONE'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE lead_statuses MODIFY COLUMN `type` ENUM('NONE', 'NEW', 'VIEWED', 'CONTACTED_SMS', 'CONTACTED_CALL', 'CONTACTED_EMAIL', 'MISSED', 'BAD', 'SOLD') NULL DEFAULT 'NONE'");
    }
}
