<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Repositories\SubscriberRepository;

class EmailParsingService
{
    public function __construct(private SubscriberRepository $subscriberRepository) {}
    
    /**
     * Parse le fichier CSV/TXT et extrait les emails
     */
    public function parseEmailFile(UploadedFile $file): array
    {
        $this->validateFile($file);
        
        $content = file_get_contents($file->getRealPath());
        $emails = $this->extractEmailsFromContent($content);
        
        return array_unique(array_filter($emails));
    }

    public function createSubscribers(UploadedFile $file): Collection
    {
        $emails = $this->parseEmailFile($file);

        if (empty($emails)) {
            throw new \InvalidArgumentException('Aucun email valide trouvé dans le fichier.');
        }

        return $this->subscriberRepository->bulkCreateFromEmails($emails);
    }

    public function getActiveSubscribers(): int
    {
        return $this->subscriberRepository->getActiveSubscribers()->count();
    }

    private function validateFile(UploadedFile $file): void
    {
        $validator = Validator::make(
            ['file' => $file],
            [
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,txt',
                    'max:2048', // 2MB max
                ]
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function extractEmailsFromContent(string $content): array
    {
        // Regex pour extraire les emails
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        preg_match_all($pattern, $content, $matches);
        
        return $matches[0] ?? [];
    }

    private function getEmails(string $data): array 
    {
        $content = str_replace(["\t", "\r", "\n", "," , ";", " "], '\n', $data);

        $list = array_map('trim', explode('\n', $content));

        $emails = [];
        
        foreach ($list as $item) {
            if (filter_var($item, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $item;
            }
        }

        return $emails;
    }

    private function getLang(array $emails): string 
    {
        $lang = "";
        
        $extensions = [
            '.fr' => 'fr',
            '.de' => 'de',
            '.it' => 'it'
        ];

        foreach($emails as $email){
            $domain = explode('@', $email);

            $extension = strrchr(end($domain), '.');

            if(array_key_exists($extension, $extensions)) {
                $lang = $extensions[$extension];
            }

            return $lang;
        }

        return 'en';
    }

    private function baseName(string $email): string
    {
        $recipient = array_map('trim', explode('@', $email));
        
        return $recipient[0];
    }    
}
