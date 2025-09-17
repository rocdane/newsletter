<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public static function trackDelivered(Email $email, array $metadata = []): self
    {
        return self::create([
            'email_id' => $email->id,
            'type' => 'delivered',
            'metadata' => $metadata,
        ]);
    }

    public static function trackOpened(Email $email, array $metadata = []): self
    {
        return self::firstOrCreate(
            [
                'email_id' => $email->id,
                'type' => 'opened',
            ],
            ['metadata' => $metadata]
        );
    }

    public static function trackClicked(Email $email, array $metadata = []): self
    {
        return self::create([
            'email_id' => $email->id,
            'type' => 'clicked',
            'metadata' => $metadata,
        ]);
    }

    public static function trackUnsuscribed(Email $email, array $metadata = []): self
    {
        return self::create([
            'email_id' => $email->id,
            'type' => 'unsuscribed',
            'metadata' => $metadata,
        ]);
    }
}
