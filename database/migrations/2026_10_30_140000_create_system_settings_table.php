<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->longText('value');
            $table->timestamps();
        });
        // One-time import of the former encrypted file. All subsequent reads/writes use DB.
        $path = config('system-settings.path');
        if ($path && is_file($path)) {
            try {
                $values = json_decode(Crypt::decryptString(file_get_contents($path)), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($values)) throw new \RuntimeException('Format pengaturan lama tidak valid.');
                DB::transaction(function () use ($values) {
                    foreach ($values as $key => $value) DB::table('system_settings')->insert([
                        'key' => $key, 'value' => Crypt::encryptString(serialize($value)),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                });
            } catch (\Throwable $exception) {
                Schema::dropIfExists('system_settings');
                throw $exception;
            }
        }
    }

    public function down(): void { Schema::dropIfExists('system_settings'); }
};
