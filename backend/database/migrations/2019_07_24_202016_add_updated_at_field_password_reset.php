<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedAtFieldPasswordReset extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::table('password_resets', function (Blueprint $blueprint) {
            $blueprint->softDeletes();
            $blueprint->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Schema::table('password_resets', function (Blueprint $blueprint) {
            $blueprint->dropSoftDeletes();
            $blueprint->dropColumn(['updated_at']);
        });
    }
}
