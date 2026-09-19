<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table using text for encryption
        Schema::create('publishing_songwriters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->string('email');

            $table->unique(['profileid', 'email']);
            $table->text('firstname');
            $table->text('lastname')->nullable();
            $table->text('phone')->nullable();

            $table->text('address1')->nullable();
            $table->text('address2')->nullable();

            $table->text('city')->nullable();
            $table->text('state')->nullable();
            $table->string('provinceid')->nullable();
            $table->string('cityid')->nullable();
            $table->string('districtid')->nullable();
            $table->string('villageid')->nullable();
            
            $table->text('zip')->nullable();
            $table->string('country', 2)->default('ID');

            $table->text('bankname')->nullable();
            $table->text('bankbranch')->nullable();
            $table->text('accountname')->nullable();
            $table->text('accountnumber')->nullable();

            $table->text('ktp_num')->nullable();
            $table->text('npwppersonal_num')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_songwriters');
    }
};
