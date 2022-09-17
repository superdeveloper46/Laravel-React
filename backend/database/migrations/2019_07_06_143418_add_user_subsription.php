<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserSubsription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->integer('max_agency_companies')->nullable();
            $blueprint->string('subscription_type', 255)->nullable();
            $blueprint->string('uuid', 255)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['max_agency_companies', 'subscription_type', 'uuid']);
        });
    }
}
