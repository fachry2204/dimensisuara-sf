<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $adminReferences = ['access_user_roles'=>'user_id', 'announcements'=>'user_id', 'broadcasts'=>'created_by'];

    public function up(): void
    {
        $ids = DB::table('access_user_roles')->distinct()->pluck('user_id')->all();
        // Some staff also own a customer catalogue. Keep that customer identity;
        // only the Admin model can read the migrated role assignments.
        $customerIds = DB::table('user_profiles')->whereIn('userid', $ids)->pluck('userid')->unique()->all();
        foreach ($this->adminReferences + ['report_imports'=>'userid'] as $table=>$column) {
            if (DB::table($table)->whereNotNull($column)->whereNotIn($column, $ids)->exists()) {
                throw new RuntimeException("Review non-admin authors in {$table} before separating accounts.");
            }
        }

        Schema::create('user_admin', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->bigInteger('avatarid')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        foreach (['logs', 'release_histories'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('admin_user_id')->nullable()->constrained('user_admin')->nullOnDelete();
            });
        }

        // Copy the raw password hash, IDs, timestamps and remember token without rehashing.
        DB::transaction(function () use ($ids) {
            foreach (DB::table('users')->whereIn('id', $ids)->get() as $admin) {
                DB::table('user_admin')->insert((array) $admin);
            }
        });
        foreach ($this->adminReferences as $table=>$column) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('id')->on('user_admin')->onDelete($table === 'announcements' ? 'set null' : 'cascade'));
        }
        Schema::table('report_imports', fn (Blueprint $t) => $t->foreign('userid')->references('id')->on('user_admin')->restrictOnDelete());

        DB::transaction(function () use ($ids, $customerIds) {
            foreach (['logs'=>'userid', 'release_histories'=>'user_id'] as $table=>$column) {
                DB::table($table)->whereIn($column, $ids)->update(['admin_user_id'=>DB::raw($column), $column=>null]);
            }
            foreach (['notifications'=>['notifiable_type','notifiable_id'], 'filestorage'=>['owner_type','owner_id']] as $table=>[$type,$id]) {
                if (Schema::hasTable($table)) DB::table($table)->where($type, App\Models\User\User::class)->whereIn($id, $ids)->update([$type=>App\Models\User\Admin::class]);
            }
            DB::table('password_reset_tokens')->whereIn('email', DB::table('user_admin')->select('email'))->delete();
            DB::table('users')->whereIn('id', $ids)->whereNotIn('id', $customerIds)->delete();
        });
    }

    public function down(): void
    {
        $ids = DB::table('user_admin')->pluck('id')->all();
        if (DB::table('users')->whereIn('id', $ids)->exists()
            || DB::table('users')->whereIn('email', DB::table('user_admin')->select('email'))->exists()) {
            throw new RuntimeException('Rollback stopped: admin/customer identities overlap. Resolve IDs and emails before merging tables.');
        }
        foreach (DB::table('user_admin')->get() as $admin) DB::table('users')->insert((array) $admin);
        foreach ($this->adminReferences as $table=>$column) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('id')->on('users')->onDelete($table === 'announcements' ? 'set null' : 'cascade'));
        }
        Schema::table('report_imports', fn (Blueprint $t) => $t->dropForeign(['userid']));
        foreach (['logs'=>'userid','release_histories'=>'user_id'] as $table=>$column) {
            DB::table($table)->whereNotNull('admin_user_id')->update([$column=>DB::raw('admin_user_id')]);
            Schema::table($table, function (Blueprint $t) { $t->dropForeign(['admin_user_id']); $t->dropColumn('admin_user_id'); });
        }
        foreach (['notifications'=>['notifiable_type','notifiable_id'], 'filestorage'=>['owner_type','owner_id']] as $table=>[$type,$id]) {
            if (Schema::hasTable($table)) DB::table($table)->where($type, App\Models\User\Admin::class)->update([$type=>App\Models\User\User::class]);
        }
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::drop('user_admin');
    }
};
