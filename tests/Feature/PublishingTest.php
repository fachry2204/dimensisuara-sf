<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\Profile;
use App\Models\Publishing\Publishing;
use App\Models\Publishing\Signee;
use App\Services\Publishing\PublishingService;
use Illuminate\Support\Facades\Notification;

class PublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function createProfile(User $user): Profile
    {
        $profile = new Profile([
            'firstname' => 'Creator',
            'lastname' => 'User',
            'phone' => '1234567890',
            'country' => 'ID',
            'address1' => 'Test Address 1',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'bankname' => 'Test Bank',
            'bankbranch' => 'Test Branch',
            'accountname' => 'Test Account',
            'accountnumber' => '1234567890',
        ]);
        $profile->userid = $user->id;
        $profile->save();
        return $profile;
    }

    public function test_creator_signee_is_auto_approved_on_publishing(): void
    {
        // 1. Create a user and a profile
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        $this->actingAs($user);

        // 2. Mock songwriters array with current user (creator) and another pending signee
        $songwriters = [
            [
                'name' => 'Creator User',
                'email' => 'creator@example.com',
                'role' => 'author',
                'share' => 50.0,
            ],
            [
                'name' => 'Other Writer',
                'email' => 'other@example.com',
                'role' => 'composer',
                'share' => 50.0,
            ]
        ];

        // 3. Create a draft publishing
        $service = app(PublishingService::class);
        $publishing = $service->create([
            'id' => null,
            'title' => 'Test Song',
            'status' => 'pending',
            'songwriters' => $songwriters,
            'accept_terms' => true,
        ], $profile->id);

        // 4. Verify in DB that signees are created and creator is auto-approved
        $this->assertDatabaseHas('publishing_signees', [
            'publishingid' => $publishing->id,
            'email' => 'creator@example.com',
            'status' => 'accepted',
            'accept_terms' => 1,
        ]);

        $creatorSignee = $publishing->signees()->where('email', 'creator@example.com')->first();
        $this->assertNotNull($creatorSignee->accepted_at);

        $this->assertDatabaseHas('publishing_signees', [
            'publishingid' => $publishing->id,
            'email' => 'other@example.com',
            'status' => 'pending',
            'accept_terms' => 0,
            'accepted_at' => null,
        ]);

        // 5. Publishing request status should be 'pending' since there is still a pending signee ('other@example.com')
        $this->assertEquals('pending', $publishing->fresh()->status);
    }

    public function test_publishing_goes_directly_to_review_if_only_creator_signs(): void
    {
        // 1. Create a user and a profile
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        $this->actingAs($user);

        // 2. Mock songwriters array with only current user
        $songwriters = [
            [
                'name' => 'Creator User',
                'email' => 'creator@example.com',
                'role' => 'author_composer',
                'share' => 100.0,
            ]
        ];

        // 3. Create and publish
        $service = app(PublishingService::class);
        $publishing = $service->create([
            'id' => null,
            'title' => 'Test Song 2',
            'status' => 'pending',
            'songwriters' => $songwriters,
            'accept_terms' => true,
        ], $profile->id);

        // 4. Since creator is the only signee, status should immediately transition to 'review'
        $this->assertEquals('review', $publishing->fresh()->status);
        $this->assertNotNull($publishing->fresh()->finished_at);
    }

    public function test_timeline_view_renders_accepted_marker(): void
    {
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Test Song',
            'status' => 'pending',
        ]);

        $signee = $publishing->signees()->create([
            'token' => \Illuminate\Support\Str::uuid(),
            'profileid' => $profile->id,
            'full_name' => 'Creator User',
            'email' => 'creator@example.com',
            'role' => 'author',
            'share' => 50.0,
            'status' => 'accepted',
            'accept_terms' => true,
            'accepted_at' => now(),
        ]);

        // Load signees relation
        $publishing->load('signees');

        // Render component
        $view = $this->view('components.publishing', [
            'publishing' => $publishing,
            'isDetailed' => true,
            'allowEdit' => true,
            'resendRoute' => 'publishing.index', // mock route names
            'contractRoute' => 'publishing.index',
        ]);

        $view->assertSee('Request Accepted');
    }

    public function test_guest_can_approve_publishing_contract_with_signature(): void
    {
        // 1. Create a user and a profile
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        // 2. Create publishing request and pending signee
        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Test Song to Sign',
            'status' => 'pending',
        ]);

        $signee = $publishing->signees()->create([
            'token' => \Illuminate\Support\Str::uuid(),
            'profileid' => $profile->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'composer',
            'share' => 50.0,
            'status' => 'pending',
        ]);

        // 3. Post to approval route with mock signature (base64 string of a 1x1 dark gray GIF)
        $mockSignature = 'data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';
        $response = $this->post(route('sign.publishing.approve', ['token' => $signee->token]), [
            'accept_terms' => 'on',
            'signature' => $mockSignature,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Request approved.');

        $signee->refresh();
        $this->assertEquals('accepted', $signee->status);
        $this->assertEquals(1, $signee->accept_terms);
        $this->assertNotNull($signee->accepted_at);

        // Verify file is stored in morph relation
        $this->assertNotNull($signee->signature);
        $this->assertEquals('signature', $signee->signature->type);
    }

    public function test_guest_approval_fails_without_signature(): void
    {
        // 1. Create a user and a profile
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        // 2. Create publishing request and pending signee
        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Test Song to Sign Failure',
            'status' => 'pending',
        ]);

        $signee = $publishing->signees()->create([
            'token' => \Illuminate\Support\Str::uuid(),
            'profileid' => $profile->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'composer',
            'share' => 50.0,
            'status' => 'pending',
        ]);

        // 3. Post with accept_terms but no signature or upload-signature
        $response = $this->post(route('sign.publishing.approve', ['token' => $signee->token]), [
            'accept_terms' => 'on',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        $signee->refresh();
        $this->assertEquals('pending', $signee->status);
        $this->assertNull($signee->accepted_at);
        $this->assertNull($signee->signature);
    }

    public function test_admin_decision_sends_notifications(): void
    {
        // 1. Create a user and a profile
        $user = User::factory()->create([
            'email' => 'creator@example.com',
        ]);
        $profile = $this->createProfile($user);

        // 2. Create a publishing request in 'review' status
        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Test Notification Song',
            'status' => 'review',
            'sync_license_type' => 'exclusive',
        ]);

        // 3. Create signees
        $creatorSignee = $publishing->signees()->create([
            'token' => \Illuminate\Support\Str::uuid(),
            'profileid' => $profile->id,
            'full_name' => 'Creator User',
            'email' => 'creator@example.com',
            'role' => 'author',
            'share' => 50.0,
            'status' => 'accepted',
            'accept_terms' => true,
            'accepted_at' => now(),
        ]);

        $guestSignee = $publishing->signees()->create([
            'token' => \Illuminate\Support\Str::uuid(),
            'profileid' => $profile->id,
            'full_name' => 'Guest User',
            'email' => 'guest@example.com',
            'role' => 'composer',
            'share' => 50.0,
            'status' => 'accepted',
            'accept_terms' => true,
            'accepted_at' => now(),
        ]);

        // 4. Test adminApprove triggers notifications
        $service = app(PublishingService::class);
        $service->adminApprove($publishing, [
            'contract_number' => '123',
            'contract_month' => '06',
            'contract_year' => '2026',
            'statement_number' => '456',
            'statement_month' => '06',
            'statement_year' => '2026',
        ]);

        // Assert customer approved notification sent
        Notification::assertSentTo(
            $profile,
            \App\Notifications\Customer\Publishing\AdminApproved::class,
            function ($notification) use ($profile) {
                // Compile the mail message and verify attachments exist
                $mailMessage = $notification->toMail($profile);
                $this->assertCount(3, $mailMessage->rawAttachments);
                return true;
            }
        );

        // Assert guest approved notification sent
        Notification::assertSentTo(
            (new \Illuminate\Notifications\AnonymousNotifiable)->route('mail', 'guest@example.com'),
            \App\Notifications\Guest\Publishing\AdminApproved::class,
            function ($notification, $channels, $notifiable) {
                $this->assertEquals('guest@example.com', $notifiable->routes['mail']);
                $mailMessage = $notification->toMail($notifiable);
                $this->assertCount(1, $mailMessage->rawAttachments);
                return true;
            }
        );

        // Reset fake or test adminReject
        Notification::fake();

        $publishing->status = 'review';
        $publishing->save();

        $service->adminReject($publishing, [
            'reason' => 'Missing documents',
        ]);

        // Assert customer rejected notification sent
        Notification::assertSentTo(
            $profile,
            \App\Notifications\Customer\Publishing\AdminRejected::class,
            function ($notification) use ($profile) {
                $mailMessage = $notification->toMail($profile);
                $this->assertStringContainsString('Missing documents', $mailMessage->introLines[1]);
                return true;
            }
        );

        // Assert guest rejected notification sent
        Notification::assertSentTo(
            (new \Illuminate\Notifications\AnonymousNotifiable)->route('mail', 'guest@example.com'),
            \App\Notifications\Guest\Publishing\AdminRejected::class,
            function ($notification, $channels, $notifiable) {
                $this->assertEquals('guest@example.com', $notifiable->routes['mail']);
                $mailMessage = $notification->toMail($notifiable);
                $this->assertStringContainsString('Missing documents', $mailMessage->introLines[1]);
                return true;
            }
        );
    }

    /**
     * Create a profile whose status passes the CustomerVerified middleware,
     * so route-based delete tests can reach the controller.
     */
    private function createVerifiedProfile(User $user): Profile
    {
        $profile = $this->createProfile($user);
        $profile->status = 'verified';
        $profile->save();
        return $profile;
    }

    public function test_completed_publishing_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['email' => 'guard@example.com']);
        $profile = $this->createVerifiedProfile($user);

        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Guarded Track',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertNotFound();

        $this->assertDatabaseHas('publishings', ['id' => $publishing->id]);
    }

    public function test_draft_publishing_can_be_deleted_by_owner(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $profile = $this->createVerifiedProfile($user);

        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Draft Track',
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertRedirect(route('publishing.index'));

        $this->assertDatabaseMissing('publishings', ['id' => $publishing->id]);
    }

    public function test_publishing_cannot_be_deleted_by_non_owner(): void
    {
        $owner = User::factory()->create(['email' => 'a@example.com']);
        $ownerProfile = $this->createVerifiedProfile($owner);

        $attacker = User::factory()->create(['email' => 'b@example.com']);
        $this->createVerifiedProfile($attacker);

        $publishing = Publishing::create([
            'profileid' => $ownerProfile->id,
            'title' => 'Someone Elses Draft',
            'status' => 'draft',
        ]);

        $this->actingAs($attacker)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertNotFound();

        $this->assertDatabaseHas('publishings', ['id' => $publishing->id]);
    }

    public function test_delete_service_rejects_non_deletable_status(): void
    {
        $user = User::factory()->create(['email' => 'svc-delete@example.com']);
        $profile = $this->createProfile($user);

        $publishing = Publishing::create([
            'profileid' => $profile->id,
            'title' => 'Completed',
            'status' => 'completed',
        ]);

        $service = app(PublishingService::class);

        $this->expectException(\App\Exceptions\Publishing\InvalidRequestStatusException::class);
        $service->delete($publishing);
    }

    public function test_cancel_service_rejects_null_status(): void
    {
        // Enhancement 3: a null status must fail closed, not slip past the guard.
        // The publishings.status column is NOT NULL, so this state is unreachable via
        // the DB — but the guard must still reject an in-memory model with null status
        // (the old `$publishing->status && ...` check would have let it through). The
        // guard runs before any persistence, so an unsaved instance exercises it directly.
        $publishing = new Publishing(['title' => 'Null Status']);
        $publishing->status = null;

        $service = app(PublishingService::class);

        $this->expectException(\App\Exceptions\Publishing\InvalidRequestStatusException::class);
        $service->cancel($publishing);
    }
}
