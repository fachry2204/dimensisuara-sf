<?php

namespace Tests\Feature;

use App\Database\RelationalFields;
use App\Models\Track;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrackCreditRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'track_credit_test','database.connections.track_credit_test'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ]]);
        Schema::create('tracks',function ($t) { $t->id(); $t->text('productions')->nullable(); $t->text('contributors')->nullable(); $t->timestamps(); });
        foreach (['_productions','_contributors'] as $table) {
            Schema::create($table,fn ($t)=>[$t->id(),$t->string('name')]);
            DB::table($table)->insert(['id'=>1,'name'=>$table==='_productions'?'Producer':'Vocalist']);
        }
    }

    protected function tearDown(): void
    {
        DB::purge('track_credit_test');
        parent::tearDown();
    }

    public function test_legacy_and_relational_credits_keep_names_when_roles_are_missing_or_unknown(): void
    {
        $credits=[['name'=>'Valid','role'=>1],['name'=>'Empty role','role'=>''],['name'=>'Missing role'],
            ['name'=>'Deleted role','role'=>999],['name'=>'Null role','role'=>null],['name'=>'Malformed role','role'=>[]]];
        foreach ([false,true] as $relational) {
            if ($relational) {
                (require base_path('database/migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
                RelationalFields::resetSchemaCache();
            }
            $track=Track::create(['productions'=>json_encode($credits),'contributors'=>json_encode($credits)]);
            foreach (['productions_pretty'=>'Producer','contributors_pretty'=>'Vocalist'] as $field=>$known) {
                $pretty=$track->fresh()->$field;
                $this->assertCount(6,$pretty);
                $this->assertSame($known,$pretty[0]->role);
                foreach (array_slice($pretty,1) as $credit) $this->assertSame('-',$credit->role);
                $this->assertSame('Empty role',$pretty[1]->name);
            }
            preg_match('~<tr>\s*<td>Production / Engineering</td>.*?</tr>~s',file_get_contents(resource_path('views/components/release.blade.php')),$fragment);
            $html=Blade::render($fragment[0],compact('track'));
            $this->assertStringContainsString('Empty role (-)',$html);
            $this->assertStringContainsString('Valid (Producer)',$html);
        }
    }

    public function test_empty_credit_lists_remain_empty(): void
    {
        $track=Track::create(['productions'=>'[]','contributors'=>'[]']);
        $this->assertSame([],$track->productions_pretty);
        $this->assertSame([],$track->contributors_pretty);
    }
}
