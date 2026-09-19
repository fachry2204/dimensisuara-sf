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
        Schema::table('publishings', function (Blueprint $table) {
            $table->timestamp('contract_expired_at')->nullable()->after('reason');
            $table->boolean('contract_auto_renew')->default(true)->after('contract_expired_at');
        });

        $publishings = DB::table('publishings')
            ->get();

        foreach ($publishings as $publishing) {
            $contractExpiredAt = Carbon::parse($publishing->created_at)->addYears(5);

            DB::table('publishings')
                ->where('id', $publishing->id)
                ->update([
                    'contract_expired_at' => $contractExpiredAt,
                    'contract_auto_renew' => $publishing->status == 'completed' ? true : false,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropColumn(['contract_expired_at', 'contract_auto_renew']);
        });
    }
};
