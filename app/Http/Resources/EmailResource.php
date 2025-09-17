<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'status' => $this->status,
            'tracking_token' => $this->tracking_token,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Subscriber information
            'subscriber' => [
                'id' => $this->whenLoaded('suscriber', $this->suscriber?->id),
                'email' => $this->whenLoaded('suscriber', $this->suscriber?->email),
                'name' => $this->whenLoaded('suscriber', $this->suscriber?->name),
                'lang' => $this->whenLoaded('suscriber', $this->suscriber?->lang),
                'is_active' => $this->whenLoaded('suscriber', $this->suscriber?->is_active),
            ],

            // Email tracking information
            'tracking' => $this->when(
                $this->relationLoaded('metas'),
                function () {
                    $metas = $this->metas;

                    return [
                        'delivered' => $metas->where('type', 'delivered')->first()?->created_at,
                        'opened' => $metas->where('type', 'opened')->first()?->created_at,
                        'clicked' => $metas->where('type', 'clicked')->first()?->created_at,
                        'unsubscribed' => $metas->where('type', 'unsubscribed')->first()?->created_at,
                        'open_count' => $metas->where('type', 'opened')->count(),
                        'click_count' => $metas->where('type', 'clicked')->count(),
                    ];
                }
            ),
        ];
    }
}
