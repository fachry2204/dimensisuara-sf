<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {

            foreach (['track','release'] as $type) {
                $models = DB::table("{$type}s")->get();
                foreach ($models as $model) {
                    $payload = $this->generateUpdatePayload($model, $type);

                    DB::table("{$type}s")->where('id', $model->id)->update($payload);
                }
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function generateUpdatePayload($model, $class) : array 
    {
        $payload = [
            'primaryartists' => []
        ];

        if ($class == 'track') {
            $payload['featuredartists'] = [];
        }

        foreach ($payload as $type => $val) {
            if ($model->$type == null) {
                continue;
            }

            $artists = json_decode($model->$type, true);
            foreach ($artists as $artist) {
                if (is_array($artist)) {
                    $payload[$type][] = [
                        'name' => $artist['name'],
                        'social' => $artist['social'],
                        'spotify' => $artist['spotify'],
                    ];
                } else if (is_object($artist)) {
                    $payload[$type][] = [
                        'name' => $artist->name,
                        'social' => $artist->social,
                        'spotify' => $artist->spotify,
                    ];
                } else {
                    $payload[$type][] = [
                        'name' => $artist,
                        'social' => '',
                        'spotify' => '',
                    ];
                }
            }
        }

        return $payload;
    }
};
