<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealCampaignIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->exec("CREATE TABLE IF NOT EXISTS `deal_campaign_integrations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(245) NULL DEFAULT NULL,
  `deal_campaign_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `type` ENUM('NONE', 'FACEBOOK', 'ZIPPER', 'OPTIN_FORM') NULL DEFAULT NULL,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  `description` VARCHAR(245) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_deal_campaign_integrations_1_idx` (`deal_campaign_id` ASC),
  UNIQUE INDEX `uuid_UNIQUE` (`uuid` ASC),
  CONSTRAINT `fk_deal_campaign_integrations_1`
    FOREIGN KEY (`deal_campaign_id`)
    REFERENCES `deal_campaigns` (`id`)
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
        Schema::dropIfExists('deal_campaign_integrations');
    }
}
