<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealCampaignAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::getPdo()->exec("CREATE TABLE IF NOT EXISTS `deal_campaign_agents` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `deal_campaign_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `company_agent_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_deal_campaign_agents_1_idx` (`deal_campaign_id` ASC),
  INDEX `fk_deal_campaign_agents_2_idx` (`company_agent_id` ASC),
  CONSTRAINT `fk_deal_campaign_agents_1`
    FOREIGN KEY (`deal_campaign_id`)
    REFERENCES `deal_campaigns` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_deal_campaign_agents_2`
    FOREIGN KEY (`company_agent_id`)
    REFERENCES `company_agents` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_campaign_agents');
    }
}
