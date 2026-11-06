<?php

namespace Tests\Feature;

use App\Models\ReleaseHistory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseHistoryActorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'history_testing', 'database.connections.history_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        foreach (['user_admin', 'users'] as $table) {
            Schema::create($table, function ($t) { $t->id(); $t->string('name'); $t->softDeletes(); });
        }
        Schema::create('release_histories', function ($t) { $t->id(); $t->integer('user_id')->nullable(); $t->integer('admin_user_id')->nullable(); });
    }

    protected function tearDown(): void
    {
        DB::purge('history_testing');
        parent::tearDown();
    }

    public function test_history_resolves_admin_and_client_with_same_id_and_escapes_names(): void
    {
        DB::table('user_admin')->insert(['id'=>1, 'name'=>'Fachry <script>']);
        DB::table('users')->insert(['id'=>1, 'name'=>'Client Name']);
        DB::table('release_histories')->insert([
            ['user_id'=>null,'admin_user_id'=>1],
            ['user_id'=>1,'admin_user_id'=>null],
            ['user_id'=>null,'admin_user_id'=>99],
        ]);
        $histories = ReleaseHistory::with('user')->get();
        $this->assertSame('Fachry <script>', $histories[0]->user->name);
        $this->assertSame('Client Name', $histories[1]->user->name);
        $render = fn ($history) => Blade::render('<x-release-history-actor :history="$history" />', compact('history'));
        $this->assertStringContainsString('Admin: Fachry &lt;script&gt;', $render($histories[0]));
        $this->assertStringContainsString('User: Client Name', $render($histories[1]));
        $this->assertStringContainsString('Admin: Nama akun tidak tersedia', $render($histories[2]));
        $this->assertStringNotContainsString('<script>', $render($histories[0]));
    }

    public function test_published_release_without_history_keeps_panel_and_honest_empty_state(): void
    {
        $release = new \App\Models\Release(['status'=>'released']);
        $release->setRelation('histories',collect());
        $source = file_get_contents(resource_path('views/components/release.blade.php'));
        $start = strrpos(substr($source,0,strpos($source,'$hasReleaseHistory')), '    @php');
        $fragment = substr($source,$start);
        $fragment = explode("@push('css')",$fragment,2)[0];
        $html = Blade::render($fragment,compact('release'));
        $this->assertStringContainsString('Release History',$html);
        $this->assertStringContainsString('Belum ada riwayat aktivitas',$html);
        $this->assertStringContainsString('Published',$html);
        $this->assertStringContainsString('aria-expanded="true"',$html);
        $this->assertStringNotContainsString('release-timeline-item ',$html);
    }
}
