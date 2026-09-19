<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Bus;

use App\Notifications\Admin\Release\Submission;

use App\Models\User\User;
use App\Models\Release;

use App\Services\WhatsAppService;

class WhatsAppTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test(): void
    {
        $user       = User::where('email', 'eadriadi@gmail.com')->first();
        $release    = Release::latest()->first();
        
        $user->notify(new Submission($release));

        $this->assertTrue(true);
    }
}
