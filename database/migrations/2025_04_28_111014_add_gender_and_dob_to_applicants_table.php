<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGenderAndDobToApplicantsTable extends Migration
{
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->enum('gender', ['m', 'f', 'u'])->nullable()->default('u')->after('applicant_email_secondary');
            $table->date('dob')->nullable()->after('gender');
        });
    }

    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('gender');
            $table->dropColumn('dob');
        });
    }
}
