<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->exec('CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(245) NULL DEFAULT NULL,
  `email` VARCHAR(245) NULL DEFAULT NULL,
  `phone` VARCHAR(245) NULL DEFAULT NULL,
  `metadata` JSON NULL,
  `lead_status_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `deal_campaign_integration_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_leads_1_idx` (`deal_campaign_integration_id` ASC),
  INDEX `fk_leads_2_idx` (`lead_status_id` ASC),
  CONSTRAINT `fk_leads_1`
    FOREIGN KEY (`deal_campaign_integration_id`)
    REFERENCES `deal_campaign_integrations` (`deal_campaign_id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_leads_2`
    FOREIGN KEY (`lead_status_id`)
    REFERENCES `lead_statuses` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}
