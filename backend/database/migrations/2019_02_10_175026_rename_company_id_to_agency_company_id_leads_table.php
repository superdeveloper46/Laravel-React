<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameCompanyIdToAgencyCompanyIdLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=0;');
            $table->renameColumn('company_id', 'agency_company_id');
            $table->dropForeign('leads_company_id_foreign');
            $table->foreign('agency_company_id')->references('id')->on('agency_companies');
            DB::getPdo()->query('SET FOREIGN_KEY_CHECKS=1;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
