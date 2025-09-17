<?php

namespace App\Repositories;

use App\Models\Email;
use App\Models\Campaign;
use Illuminate\Support\Collection;

class EmailRepository
{
    public function createBulkEmails(
        Collection $subscribers,
        string $subject,
        string $content,
        Campaign $campaign
    ): Collection {
        $emails = collect();

        foreach ($subscribers as $subscriber) {
            $email = Email::create([
                'subscriber_id' => $subscriber->id,
                'email_campaign_id' => $campaign->id,
                'subject' => $subject,
                'content' => $content,
                'status' => 'pending',
            ]);

            $emails->push($email);
        }

        return $emails;
    }

    public function getPendingEmails(): Collection
    {
        return Email::pending()->with('subscriber')->get();
    }

    public function getSentEmails(): Collection
    {
        return Email::sent()->with('subscriber')->get();
    }

    public function findByTrackingToken(string $token): ?Email
    {
        return Email::where('tracking_token', $token)
            ->with('subscriber')
            ->first();
    }

    public function updateEmailStatus(Email $email, string $status): Email
    {
        $email->status = $status;
        $email->save();

        return $email;
    }

    public function getEmailsByCampaign(Campaign $campaign): Collection
    {
        return Email::where('campaign_id', $campaign->id)
            ->with('subscriber')
            ->get();
    }
}
