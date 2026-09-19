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
        // Add sync_license_type, duration_minute, and duration_second columns
        Schema::table('publishings', function (Blueprint $table) {
            $table->string('sync_license_type')->after('sample')->default('exclusive');
            $table->string('duration_minute')->after('performer')->default(0);
            $table->string('duration_second')->after('duration_minute')->default(0);
            $table->boolean('accept_terms')->after('note')->default(false);
        });

        // Migrate duration data
        DB::table('publishings')
            ->where('duration', '1')
            ->update([
                'duration_minute' => '1',
                'duration_second' => '0'
            ]);

        DB::table('publishings')
            ->where('duration', '2')
            ->update([
                'duration_minute' => '2',
                'duration_second' => '0'
            ]);

        DB::table('publishings')
            ->where('duration', '3')
            ->update([
                'duration_minute' => '3',
                'duration_second' => '0'
            ]);

        DB::table('publishings')
            ->where('duration', '4')
            ->update([
                'duration_minute' => '4',
                'duration_second' => '0'
            ]);

        DB::table('publishings')
            ->where('duration', '5')
            ->update([
                'duration_minute' => '5',
                'duration_second' => '0'
            ]);

        $customs = DB::table('publishings')
            ->where('duration', '6')
            ->get();

        foreach($customs as $custom) {
            $duration = explode(':', $custom->duration_custom);
            $minute = $duration[0] ?? 0;
            $second = $duration[1] ?? 0;

            DB::table('publishings')
                ->where('id', $custom->id)
                ->update([
                    'duration_minute' => $minute,
                    'duration_second' => $second
                ]);
        }
        
        // Drop legacy column
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('duration_custom');
            $table->dropColumn('synchronization_rights');
            $table->dropColumn('mechanical_rights');
            $table->dropColumn('performing_rights');
            $table->dropColumn('print_rights');
            $table->dropColumn('other_rights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add sync_license_type, duration_minute, and duration_second columns
        Schema::table('publishings', function (Blueprint $table) {
            
            // Add duration and duration_custom columns
            $table->string('duration')->after('performer')->nullable();
            $table->string('duration_custom')->after('duration')->nullable();
            
            // Add synchronization_rights, mechanical_rights, performing_rights, print_rights, other_rights columns
            $table->boolean('synchronization_rights')->after('sample')->nullable();
            $table->boolean('mechanical_rights')->after('synchronization_rights')->nullable();
            $table->boolean('performing_rights')->after('mechanical_rights')->nullable();
            $table->boolean('print_rights')->after('performing_rights')->nullable();
            $table->boolean('other_rights')->after('print_rights')->nullable();
        });

        $publishings = DB::table('publishings')->get();
        foreach($publishings as $publishing) {
            if($publishing->duration_second == 0) {
                $duration = $publishing->duration_minute;
                $duration_custom = null;
            } else {
                $duration = 6;
                $duration_custom = $publishing->duration_minute . ':' . $publishing->duration_second;
            }

            DB::table('publishings')
                ->where('id', $publishing->id)
                ->update([
                    'synchronization_rights' => true,
                    'mechanical_rights' => true,
                    'performing_rights' => true,
                    'print_rights' => true,
                    'other_rights' => true,
                    'duration' => $duration,
                    'duration_custom' => $duration_custom
                ]);
        }

        Schema::table('publishings', function (Blueprint $table) {
            $table->dropColumn('sync_license_type');
            $table->dropColumn('duration_minute');
            $table->dropColumn('duration_second');
            $table->dropColumn('accept_terms');
        });
    }
};
