<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CampaignCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = CampaignResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Get collection summary statistics
     */
    private function getSummary(): array
    {
        $campaigns = $this->collection;

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'processing')->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'failed_campaigns' => $campaigns->where('status', 'failed')->count(),
            'total_emails_sent' => $campaigns->sum('sent_emails'),
            'total_emails_failed' => $campaigns->sum('failed_emails'),
        ];
    }
}
