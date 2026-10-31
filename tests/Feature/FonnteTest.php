<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'fonnte_testing','database.connections.fonnte_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false], 'system-settings.whatsapp.token'=>'fonnte-test-secret','system-settings.whatsapp.number'=>null]);
        Schema::create('notify_whatsapp', function ($table) { $table->id();$table->string('deviceid');$table->string('gwid')->nullable();$table->string('phone');$table->text('message');$table->timestamp('added_on'); });
        Http::preventStrayRequests();
    }
    protected function tearDown(): void { DB::purge('fonnte_testing'); parent::tearDown(); }

    public function test_send_uses_fonnte_authorization_and_form_payload(): void
    {
        Http::fake(['https://api.fonnte.com/send'=>Http::response(['status'=>true,'id'=>['1234'],'process'=>'pending'],200)]);
        $this->assertTrue(WhatsAppService::singleSend('0812-3456-7890','Pesan test'));
        Http::assertSent(fn ($request) => $request->url()==='https://api.fonnte.com/send'
            && $request->hasHeader('Authorization','fonnte-test-secret') && $request['target']==='6281234567890'
            && $request['countryCode']==='0' && $request['message']==='Pesan test' && !isset($request['api_key']) && !isset($request['sender']));
        $this->assertSame('1234',DB::table('notify_whatsapp')->value('gwid'));
        $this->assertSame('fonnte',DB::table('notify_whatsapp')->value('deviceid'));
    }

    public function test_provider_rejection_never_records_success_or_exposes_raw_response(): void
    {
        Http::fake(['*'=>Http::response(['Status'=>false,'reason'=>'token invalid fonnte-test-secret'],200)]);
        try { WhatsAppService::singleSend('+62 81234567890','Private message');$this->fail('Must reject'); }
        catch (\RuntimeException $e) { $this->assertStringNotContainsString('fonnte-test-secret',$e->getMessage()); }
        $this->assertSame(0,DB::table('notify_whatsapp')->count());
    }

    public function test_missing_token_and_multiple_target_injection_do_not_send(): void
    {
        config(['system-settings.whatsapp.token'=>null]);
        try { WhatsAppService::singleSend('081234567890','Text');$this->fail('Missing token'); } catch (\RuntimeException $e) { $this->assertStringContainsString('Token Fonnte',$e->getMessage()); }
        config(['system-settings.whatsapp.token'=>'secret']);
        try { WhatsAppService::singleSend('081234567890,08111111111','Text');$this->fail('Multiple recipients'); } catch (\InvalidArgumentException $e) { $this->assertStringContainsString('tidak valid',$e->getMessage()); }
        Http::assertNothingSent();
    }
}
