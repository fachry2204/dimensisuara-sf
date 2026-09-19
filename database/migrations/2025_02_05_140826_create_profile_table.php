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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userid')->constrained(
                table: 'users',
                column: 'id'
            )->onDelete('restrict');

            $table->string('old_id')->nullable();
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('phone', 20);
            
            $table->boolean('is_company')->default(false);
            $table->string('companyname', 100)->nullable();

            $table->text('address1');
            $table->text('address2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('zip', 20);
            $table->string('country', 10);

            $table->string('bankname', 100);
            $table->string('bankbranch', 100);
            $table->string('accountname', 100);
            $table->string('accountnumber', 50);
            
            $table->boolean('feature_aggregator')->default(false);
            $table->boolean('feature_publishing')->default(false);
            $table->boolean('feature_mcn')->default(false);

            $table->text('dir_root')->nullable();
            $table->text('dir_identity')->nullable();
            $table->text('dir_aggregator')->nullable();
            $table->text('dir_publishing')->nullable();
            $table->text('dir_mcn')->nullable();
            $table->text('dir_release')->nullable();

            $table->string('ktp_num')->nullable();
            $table->string('npwppersonal_num')->nullable();

            $table->enum('status', ['verified', 'blocked', 'pending'])->default('pending');

            $table->text('reason')->nullable();
            $table->date('delete_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};