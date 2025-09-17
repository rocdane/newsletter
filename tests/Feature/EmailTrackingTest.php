<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\EmailMeta;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pixel_tracking_works(): void
    {
        $subscriber = Subscriber::create(['email' => 'test@example.com']);

        $email = Email::create([
            'subscriber_id' => $subscriber->id,
            'subject' => 'Test',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        $response = $this->get(route('email.tracking.pixel', ['token' => $email->tracking_token]));
        $response->assertSuccessful();
        $response->assertHeader('content-type', 'image/gif');

        $this->assertTrue($email->metas()->where('type', 'opened')->exists());
    }

    public function test_click_tracking_works(): void
    {
        $subscriber = Subscriber::create(['email' => 'test@example.com']);
        $email = Email::create([
            'subscriber_id' => $subscriber->id,
            'subject' => 'Test',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        $originalUrl = 'http://localhost';
        $encodedUrl = base64_encode($originalUrl);

        $response = $this->get(route('email.tracking.click', [
            'token' => $email->tracking_token,
            'url' => $encodedUrl
        ]));

        $response->assertRedirect($originalUrl);

        $this->assertTrue($email->metas()->where('type', 'clicked')->exists());
        
        $clickMeta = $email->metas()->where('type', 'clicked')->first();
        $this->assertEquals($originalUrl, $clickMeta->metadata['clicked_url']);
    }

    public function test_email_meta_tracking_methods(): void
    {
        $subscriber = Subscriber::create(['email' => 'test@example.com']);

        $email = Email::create([
            'subscriber_id' => $subscriber->id,
            'subject' => 'Test',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        EmailMeta::trackDelivered($email, ['test' => 'data']);
        $this->assertTrue($email->metas()->where('type', 'delivered')->exists());

        EmailMeta::trackOpened($email, ['ip' => '127.0.0.1']);
        $this->assertTrue($email->metas()->where('type', 'opened')->exists());

        EmailMeta::trackClicked($email, ['url' => 'http://localhost']);
        $this->assertTrue($email->metas()->where('type', 'clicked')->exists());
    }
}