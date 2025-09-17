<?php

namespace App\Repositories;

use App\Models\Subscriber;
use Illuminate\Support\Collection;

class SubscriberRepository
{
    public function findOrCreateByEmail(string $email, array $additionalData = []): Subscriber
    {
        return Subscriber::firstOrCreate(
            ['email' => $email],
            $additionalData
        );
    }

    public function bulkCreateFromEmails(array $emails): Collection
    {
        $suscribers = collect();

        foreach ($emails as $email) {
            $suscriber = $this->findOrCreateByEmail($email);
            $suscribers->push($suscriber);
        }

        return $suscribers;
    }

    public function getActiveSubscribers(): Collection
    {
        return Subscriber::active()->get();
    }
}
