<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('publishingid');
            $table->string('email')->nullable()->after('full_name');
            $table->string('ktp_num')->nullable()->after('email');
            $table->string('publisher')->nullable()->after('ktp_num');
            $table->string('lmk')->nullable()->after('publisher');
            $table->dropForeign(['songwriterid']);
            $table->dropColumn('songwriterid');
            $table->dropColumn('publishers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'email', 'ktp_num', 'publisher', 'lmk']);
            $table->unsignedBigInteger('songwriterid')->nullable()->after('profileid');
            $table->json('publishers')->nullable()->after('share');

            $table->foreign('songwriterid')
                ->references('id')
                ->on('publishing_songwriters')
                ->onDelete('set null');
        });
    }
};
