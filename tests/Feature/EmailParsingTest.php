<?php

namespace Tests\Feature;

use App\Services\EmailParsingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class EmailParsingTest extends TestCase
{
    private EmailParsingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmailParsingService::class);
    }

    public function test_can_parse_csv_file(): void
    {
        $csvContent = "test1@example.com,Test User 1\ntest2@example.com,Test User 2";
        $file = UploadedFile::fake()->createWithContent('emails.csv', $csvContent);

        $emails = $this->service->parseEmailFile($file);

        $this->assertCount(2, $emails);
        $this->assertContains('test1@example.com', $emails);
        $this->assertContains('test2@example.com', $emails);
    }

    public function test_can_parse_txt_file(): void
    {
        $txtContent = "user1@test.com\nuser2@test.com\nuser3@test.com";
        $file = UploadedFile::fake()->createWithContent('emails.txt', $txtContent);

        $emails = $this->service->parseEmailFile($file);

        $this->assertCount(3, $emails);
        $this->assertContains('user1@test.com', $emails);
    }

    public function test_filters_invalid_emails(): void
    {
        $content = "valid@example.com\ninvalid-email\nanother@test.com";
        $file = UploadedFile::fake()->createWithContent('emails.txt', $content);

        $emails = $this->service->parseEmailFile($file);

        $this->assertCount(2, $emails);
        $this->assertNotContains('invalid-email', $emails);
    }

    public function test_removes_duplicate_emails(): void
    {
        $content = "test@example.com\ntest@example.com\nother@example.com";
        $file = UploadedFile::fake()->createWithContent('emails.txt', $content);
        $emails = $this->service->parseEmailFile($file);
        $this->assertCount(2, $emails);
    }

    public function test_throws_exception_for_invalid_file_type(): void
    {
        $this->expectException(ValidationException::class);
        $file = UploadedFile::fake()->create('invalid.pdf', 1000, 'application/pdf');
        $this->service->parseEmailFile($file);
    }
}
