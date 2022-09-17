<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::getPdo()->exec("
       CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` INT(10) UNSIGNED NOT NULL,
  `device_token` VARCHAR(245) NULL DEFAULT NULL,
  `type` ENUM('NONE', 'IOS', 'ANDROID', 'WEB', 'DESCKTOP') NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_devices_1_idx` (`agent_id` ASC),
  CONSTRAINT `fk_devices_1`
    FOREIGN KEY (`agent_id`)
    REFERENCES `users` (`id`)
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
        Schema::dropIfExists('devices');
    }
}
