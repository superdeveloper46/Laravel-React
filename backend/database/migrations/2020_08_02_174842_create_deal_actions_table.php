<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deal_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('deal_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table
                ->enum('type', ['NONE', 'SMS', 'EMAIL', 'BLIND_CALL', 'CHANGE_STATUS', 'PUSH_NOTIFICATION'])
                ->default('NONE');
            $table->boolean('has_lead_reply')->default(0);
            $table->string('lead_reply_contains')->nullable();
            $table->boolean('is_root')->default(0);
            $table->json('object')->nullable();
            $table->string('delay_time', 255)->default(0);
            $table->enum('delay_type', ['NONE', 'TIME', 'DAYS'])->default('NONE');
            $table->foreign('deal_id')->references('id')->on('deals');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_actions');
    }
}
