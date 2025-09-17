<?php

namespace Tests\Feature;

use App\Jobs\ProcessCampaignJob;
use App\Services\CampaignService;
use App\Models\Campaign;
use App\Models\Subscriber;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_can_create_email_campaign_with_csv_file(): void
    {
        $csvContent = "test1@example.com\ntest2@example.com\ntest3@example.com";
        
        $file = UploadedFile::fake()->createWithContent('emails.csv', $csvContent);

        $campaignService = app(CampaignService::class);

        $campaign = $campaignService->createCampaign(
            $file,
            'Test Subject',
            'Test Content',
            'Test Campaign'
        );

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->name);
        $this->assertEquals('Test Subject', $campaign->subject);
        $this->assertEquals('Test Content', $campaign->content);
        $this->assertEquals(3, $campaign->total_emails);

        $this->assertEquals(3, Suscriber::count());
        
        $this->assertEquals(3, $campaign->emails()->count());

        Queue::assertPushed(ProcessCampaignJob::class);
    }

    public function test_duplicate_emails_are_handled_correctly(): void
    {
        Suscriber::create(['email' => 'test1@example.com']);


        $csvContent = "test1@example.com\ntest2@example.com\ntest1@example.com";
        $file = UploadedFile::fake()->createWithContent('emails.csv', $csvContent);

        $campaignService = app(CampaignService::class);
        $campaign = $campaignService->createCampaign($file, 'Subject', 'Content');

        $this->assertEquals(2, Suscriber::count());

        $this->assertEquals(2, $campaign->total_emails);
    }

    public function test_invalid_file_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        
        $campaignService = app(CampaignService::class);
        $campaignService->createCampaign($file, 'Subject', 'Content');
    }

    public function test_empty_file_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->expectExceptionMessage('Aucun email valide trouvé dans le fichier.');

        $file = UploadedFile::fake()->createWithContent('empty.csv', '');
        
        $campaignService = app(CampaignService::class);
        $campaignService->createCampaign($file, 'Subject', 'Content');
    }

    public function test_file_with_invalid_emails_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun email valide trouvé dans le fichier.');

        $csvContent = "not-an-email\ninvalid@\n@missing.com";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);
        
        $campaignService = app(CampaignService::class);
        $campaignService->createCampaign($file, 'Subject', 'Content');
    }

    public function test_campaign_progress_tracking(): void
    {
        $campaign = Campaign::create([
            'name' => 'Test',
            'subject' => 'Test',
            'content' => 'Test',
            'total_emails' => 10,
            'sent_emails' => 3,
            'failed_emails' => 1,
        ]);

        $this->assertEquals(40.0, $campaign->progress_percentage);
        
        $campaign->incrementSent();
        $this->assertEquals(4, $campaign->sent_emails);
        
        $campaign->incrementFailed();
        $this->assertEquals(2, $campaign->failed_emails);
    }

    protected function createTestCsvFile(array $emails): UploadedFile
    {
        $content = implode("\n", $emails);
        return UploadedFile::fake()->createWithContent('test.csv', $content);
    }

    protected function createTestTxtFile(array $emails): UploadedFile
    {
        $content = implode("\n", $emails);
        return UploadedFile::fake()->createWithContent('test.txt', $content);
    }
}