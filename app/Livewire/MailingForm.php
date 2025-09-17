<?php

namespace App\Livewire;

use App\Jobs\MailingProgress;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class MailingForm extends Component
{
    use WithFileUploads;

    public $file;

    public $subject;

    public $message;

    public $submitted = false;

    protected $listeners = ['submit' => 'send'];

    private function getEmails(string $data): array
    {
        $content = str_replace(["\t", "\r", "\n", ',', ';', ' '], '\n', $data);

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
        $lang = '';

        $extensions = [
            '.fr' => 'fr',
            '.de' => 'de',
            '.it' => 'it',
        ];

        foreach ($emails as $email) {
            $domain = explode('@', $email);

            $extension = strrchr(end($domain), '.');

            if (array_key_exists($extension, $extensions)) {
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
                'body' => $data['body'],
            ];
        }

        return $emails;
    }

    public function send()
    {
        $data = $this->validate([
            'file' => 'required|mimes:txt',
            'subject' => 'required|max:100',
            'message' => 'required|max:5000',
        ]);

        $file = $data['file'];

        $recipients = file_get_contents($file->getRealPath());

        $emails = $this->prepare([
            'recipients' => $recipients,
            'subject' => $data['subject'],
            'body' => $data['message'],
        ]);

        $jobs = [];

        foreach ($emails as $index => $email) {
            $delay = now()->addSeconds($index * 5);
            $jobs[] = (new MailingProgress($email))->delay($delay);
        }

        $batch = Bus::batch($jobs)->dispatch();

        if (! is_null($batch)) {
            Cache::put('mailing_batch_id', $batch->id);
        } else {
            Cache::put('mailing_batch_id', 0);
        }

        return to_route('mailing')->with('success', 'Mailing processed successfully.');
    }

    public function render()
    {
        return view('livewire.mailing-form');
    }
}
