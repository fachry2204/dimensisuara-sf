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
        Schema::create('user_profile_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->constrained(
                table: 'user_profiles', 
                column: 'id'
            )->onDelete('restrict');
            
            $table->string('type', 50);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('user_profile_identities');
    }
};
