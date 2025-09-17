<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Email extends Model
{
    protected $fillable = [
        'subscriber_id',
        'campaign_id',
        'subject',
        'content',
        'status',
        'tracking_token',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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

    public function metas(): HasMany
    {
        return $this->hasMany(EmailMeta::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeOpened($query)
    {
        return $query->where('status', 'opened');
    }

    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
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
