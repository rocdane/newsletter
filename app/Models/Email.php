<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Enums\EmailStatus;

class Email extends Model
{
    protected $fillable = [
        'subscriber_id',
        'campaign_id',
        'status',
        'tracking_token',
        'delivered_at',
        'clicked_at',
        'metadata',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'clicked_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($email) {
            if (empty($email->tracking_token)) {
                $email->tracking_token = Str::uuid();
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

    public function scopeClicked($query)
    {
        return $query->where('status', EmailStatus::CLICKED->value);
    }

    public function markAsDelivered(): void
    {
        $this->status = EmailStatus::DELIVERED->value;
        $this->delivered_at = now();
        $this->save();
    }

    public function markAsClicked(): void
    {
        $this->status = EmailStatus::CLICKED->value;
        $this->clicked_at = now();
        $this->save();
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
