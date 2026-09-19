<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('upload_status');
            $table->timestamp('contract_expired_at')->nullable()->after('verified_at');
            $table->boolean('contract_auto_renew')->default(true)->after('contract_expired_at');
        });

        // Auto fill data using release history
        $histories = DB::table('release_histories')
            ->where('action','adminApprove')
            ->get();

        foreach ($histories as $history) {
            $verifiedAt = Carbon::parse($history->created_at);

            DB::table('releases')

                ->where('id', $history->release_id)
                ->where('status', 'released')
                ->update([
                    'verified_at' => $verifiedAt,
                    'contract_expired_at' => $verifiedAt->copy()->addYears(5),
                    'contract_auto_renew' => true,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'contract_expired_at', 'contract_auto_renew']);
        });
    }
};
