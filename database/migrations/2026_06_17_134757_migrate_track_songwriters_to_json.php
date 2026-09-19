<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->json('songwriters')->nullable()->after('iswc');
        });

        // Migrate data
        $tracks = DB::table('tracks')->get();
        foreach($tracks as $track) {
            $writers = [];
            if(!empty($track->composer)) {
                $writers[] = ['name' => $track->composer, 'role' => 'composer'];
            }
            if(!empty($track->songwriter)) {
                $writers[] = ['name' => $track->songwriter, 'role' => 'author'];
            }
            
            DB::table('tracks')
                ->where('id', $track->id)
                ->update(['songwriters' => json_encode($writers)]);
        }

        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['composer', 'songwriter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('composer', 100)->nullable();
            $table->string('songwriter', 100)->nullable();
        });

        // Best effort rollback
        $tracks = DB::table('tracks')->get();
        foreach($tracks as $track) {
            $writers = json_decode($track->songwriters, true) ?? [];
            $composer = null;
            $songwriter = null;
            
            foreach($writers as $w) {
                if($w['role'] === 'composer' && !$composer) $composer = $w['name'];
                if($w['role'] === 'author' && !$songwriter) $songwriter = $w['name'];
            }
            
            DB::table('tracks')->where('id', $track->id)->update([
                'composer' => $composer,
                'songwriter' => $songwriter
            ]);
        }

        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn('songwriters');
        });
    }
};
