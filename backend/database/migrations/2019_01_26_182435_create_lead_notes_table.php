<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::getPdo()->exec('CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT(10) UNSIGNED NOT NULL,
  `lead_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `company_agent_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `lead_status_id` INT(11) NULL DEFAULT NULL,
  `deal_action_id` INT(11) NULL DEFAULT NULL,
  `message` VARCHAR(245) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_lead_history_1_idx` (`lead_id` ASC),
  INDEX `fk_lead_history_2_idx` (`company_agent_id` ASC),
  INDEX `fk_lead_history_3_idx` (`lead_status_id` ASC),
  CONSTRAINT `fk_lead_history_1`
    FOREIGN KEY (`lead_id`)
    REFERENCES `leads` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lead_history_2`
    FOREIGN KEY (`company_agent_id`)
    REFERENCES `company_agents` (`id`)
    ON DELETE NO ACTION
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
        Schema::dropIfExists('lead_notes');
    }
}
