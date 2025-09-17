<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status,
            'total_emails' => $this->total_emails,
            'sent_emails' => $this->sent_emails,
            'failed_emails' => $this->failed_emails,
            'pending_emails' => $this->total_emails - $this->sent_emails - $this->failed_emails,
            'progress_percentage' => $this->progress_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional includes based on loaded relationships
            'emails' => EmailResource::collection($this->whenLoaded('emails')),
            'statistics' => $this->when(
                $this->relationLoaded('emails'),
                function () {
                    return $this->calculateStatistics();
                }
            ),
        ];
    }

    /**
     * Calculate campaign statistics when emails are loaded
     */
    private function calculateStatistics(): array
    {
        $emails = $this->emails;
        $total = $emails->count();

        if ($total === 0) {
            return [
                'delivery_rate' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
                'bounce_rate' => 0,
                'unsubscribe_rate' => 0,
            ];
        }

        $sent = $emails->where('status', 'sent')->count();
        $failed = $emails->where('status', 'failed')->count();

        $opened = $emails->filter(function ($email) {
            return $email->metas && $email->metas->where('type', 'opened')->count() > 0;
        })->count();

        $clicked = $emails->filter(function ($email) {
            return $email->metas && $email->metas->where('type', 'clicked')->count() > 0;
        })->count();

        $unsubscribed = $emails->filter(function ($email) {
            return $email->metas && $email->metas->where('type', 'unsubscribed')->count() > 0;
        })->count();

        return [
            'delivery_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'open_rate' => $sent > 0 ? round(($opened / $sent) * 100, 2) : 0,
            'click_rate' => $sent > 0 ? round(($clicked / $sent) * 100, 2) : 0,
            'bounce_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            'unsubscribe_rate' => $sent > 0 ? round(($unsubscribed / $sent) * 100, 2) : 0,
        ];
    }
}
