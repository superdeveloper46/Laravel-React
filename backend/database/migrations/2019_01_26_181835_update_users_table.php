<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getPdo()->exec("ALTER TABLE `users`
ADD COLUMN `role` ENUM('NONE', 'ADMIN', 'AGENCY', 'COMPANY', 'AGENT') NULL DEFAULT NULL AFTER `remember_token`,
ADD COLUMN `thumbnail` VARCHAR(245) NULL DEFAULT NULL AFTER `role`,
ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`,
CHANGE COLUMN `name` `name` VARCHAR(255) NULL DEFAULT NULL ,
CHANGE COLUMN `email` `email` VARCHAR(255) NULL DEFAULT NULL ,
CHANGE COLUMN `password` `password` VARCHAR(255) NULL DEFAULT NULL ,
CHANGE COLUMN `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
