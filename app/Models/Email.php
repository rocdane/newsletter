<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Enums\EmailStatus;
use App\Services\Metadata;

class Email extends Model
{
    use Metadata;

    protected $fillable = [
        'subscriber_id',
        'campaign_id',
        'status',
        'tracking_token',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'metadata',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($email) {
            if (empty($email->tracking_token)) {
                $email->tracking_token = Str::uuid();
            }
            
            // Initialiser les métadonnées par défaut
            if (empty($email->metadata)) {
                $email->metadata = [];
            }
        });
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', EmailStatus::PENDING->value);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', EmailStatus::DELIVERED->value);
    }

    public function scopeOpened($query)
    {
        return $query->where('status', EmailStatus::OPENED->value);
    }

    public function scopeClicked($query)
    {
        return $query->where('status', EmailStatus::CLICKED->value);
    }

    public function markAsDelivered(): void
    {
        $this->status = EmailStatus::DELIVERED->value;
        $this->delivered_at = now();
        
        $this->addMetadata('delivery', [
            'delivered_at' => now()->toISOString(),
        ]);
        
        $this->save();
    }

    public function markAsOpened(): void
    {
        $this->status = EmailStatus::OPENED->value;
        $this->opened_at = now();
        
        $this->addMetadata('open', [
            'opened_at' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ]);
        
        $this->save();
    }

    public function markAsClicked(): void
    {
        $this->status = EmailStatus::CLICKED->value;
        $this->clicked_at = now();
        
        $this->addMetadata('click', [
            'clicked_at' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ]);
        
        $this->save();
    }

    /**
     * Obtenir toutes les livraisons
     */
    public function getDeliverEvents(): array
    {
        $data = $this->getMetadata('deliver', []);

        return $this->getEvents($data);
    }

    /**
     * Obtenir toutes les ouvertures
     */
    public function getOpenEvents(): array
    {
        $data = $this->getMetadata('open', []);

        return $this->getEvents($data);
    }

    /**
     * Obtenir tous les clics
     */
    public function getClickEvents(): array
    {
        $data = $this->getMetadata('click', []);

        return $this->getEvents($data);
    }

    public function getTrackingPixelUrl(): string
    {
        return route('email.tracking.pixel', ['token' => $this->tracking_token]);
    }

    public function getTrackingClickUrl(string $originalUrl): string
    {
        return route('email.tracking.click', [
            'token' => $this->tracking_token,
            'url' => base64_encode($originalUrl),
        ]);
    }
}