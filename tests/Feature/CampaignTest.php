<?php

namespace Tests\Feature;

use App\Jobs\ProcessCampaignJob;
use App\Services\CampaignService;
use App\Services\EmailParsingService;
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
        $emailParsingService = app(EmailParsingService::class);
        
        $subscribers = $emailParsingService->createSubscribers($file);

        $campaign = $campaignService->createCampaign(
            $subscribers,
            'Test Subject',
            'Test Content',
            'Test Campaign',
            null,
            null
        );

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->name);
        $this->assertEquals('Test Subject', $campaign->subject);
        $this->assertEquals('Test Content', $campaign->content);

        Queue::assertPushed(ProcessCampaignJob::class);
    }

    public function test_duplicate_emails_are_handled_correctly(): void
    {
        Subscriber::create(['email' => 'test1@example.com']);


        $csvContent = "test1@example.com\ntest2@example.com\ntest1@example.com";
        $file = UploadedFile::fake()->createWithContent('emails.csv', $csvContent);

        $campaignService = app(CampaignService::class);
        $emailParsingService = app(EmailParsingService::class);

        $subscribers = $emailParsingService->createSubscribers($file);

        $campaign = $campaignService->createCampaign($subscribers, 'Subject', 'Content',null,null,null);

        $this->assertEquals(2, Subscriber::count());
    }

    public function test_invalid_file_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        
        $campaignService = app(CampaignService::class);
        $emailParsingService = app(EmailParsingService::class);

        $subscribers = $emailParsingService->createSubscribers($file);
        $campaignService->createCampaign($subscribers, 'Subject', 'Content');
    }

    
    public function test_empty_file_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->expectExceptionMessage('Aucun email valide trouvé dans le fichier.');

        $file = UploadedFile::fake()->createWithContent('empty.csv', '');
        
        $campaignService = app(CampaignService::class);
        $emailParsingService = app(EmailParsingService::class);
        
        $subscribers = $emailParsingService->createSubscribers($file);
        $campaignService->createCampaign($subscribers, 'Subject', 'Content',null,null,null);
    }

    public function test_file_with_invalid_emails_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun email valide trouvé dans le fichier.');

        $csvContent = "not-an-email\ninvalid@\n@missing.com";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);
        
        $campaignService = app(CampaignService::class);
        $emailParsingService = app(EmailParsingService::class);

        $subscribers = $emailParsingService->createSubscribers($file);
        $campaignService->createCampaign($subscribers, 'Subject', 'Content',null,null,null);
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