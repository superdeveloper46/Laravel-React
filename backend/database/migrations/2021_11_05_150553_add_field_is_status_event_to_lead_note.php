<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldIsStatusEventToLeadNote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_notes', function (Blueprint $table) { 
            $table->integer('is_new')->default(1)->unsigned()->nullable();
            $table->integer('is_status_event')->default(0)->unsigned()->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_note', function (Blueprint $table) {
            //
        });
    }
}
 
