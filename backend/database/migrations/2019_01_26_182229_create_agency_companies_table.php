<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgencyCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->exec('CREATE TABLE IF NOT EXISTS `agency_companies` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id` INT(11) UNSIGNED NULL DEFAULT NULL,
  `company_id` INT(11) UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_agency_companies_1_idx` (`agency_id` ASC),
  INDEX `fk_agency_companies_2_idx` (`company_id` ASC),
  CONSTRAINT `fk_agency_companies_1`
    FOREIGN KEY (`agency_id`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_agency_companies_2`
    FOREIGN KEY (`company_id`)
    REFERENCES `users` (`id`)
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
        Schema::dropIfExists('agency_companies');
    }
}
