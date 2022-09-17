<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DealCampaignFacebookIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deal_campaign_facebook_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('deal_campaign_id')->unsigned();
            $table->string('fb_page_id', 255);
            $table->string('fb_form_id', 255);
            $table->string('page_name', 255)->nullable();
            $table->string('form_name', 255)->nullable();
            $table->text('fb_page_access_token');
            $table->string('fb_ad_account_id', 255)->nullable();
            $table->foreign('deal_campaign_id')->references('id')->on('deal_campaigns')->onDelete('cascade');
            $table->timestamps();
        });
        DB::getPdo()->query('
            ALTER TABLE `lead_aggregator`.`deal_campaign_facebook_integrations` 
            ADD UNIQUE INDEX `unique_fb_integration` (`deal_campaign_id`, `fb_page_id`, `fb_form_id` ASC)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_campaign_facebook_integrations');
    }
}
