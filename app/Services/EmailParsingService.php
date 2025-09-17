<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmailParsingService
{
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

    private function prepare(array $data)
    {
        $mailist = $this->getEmails($data['recipients']);
        
        $lang = $this->getLang($mailist);

        $emails = [];

        foreach ($mailist as $key => $email) {
            $emails[] = [
                'lang' => $lang,
                'name' => $this->baseName($email),
                'address' => $email,
                'subject' => $data['subject'],
                'body' => $data['body']
            ];
        }

        return $emails;
    }
    
}
