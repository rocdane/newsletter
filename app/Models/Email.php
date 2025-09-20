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
        'opened_at',
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
     * Ajouter des métadonnées à l'email
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        
        if (in_array($key, ['delivery', 'open', 'click'])) {
            if (!isset($metadata[$key])) {
                $metadata[$key] = [];
            }
            $metadata[$key][] = $value;
        } else {
            $metadata[$key] = $value;
        }
        
        $this->metadata = $metadata;
    }

    /**
     * Récupérer une métadonnée spécifique
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Vérifier si une métadonnée existe
     */
    public function hasMetadata(string $key): bool
    {
        return data_get($this->metadata, $key) !== null;
    }

    /**
     * Supprimer une métadonnée
     */
    public function removeMetadata(string $key): void
    {
        $metadata = $this->metadata ?? [];
        unset($metadata[$key]);
        $this->metadata = $metadata;
    }

    /**
     * Ajouter des informations de géolocalisation
     */
    public function addGeolocationData(array $location): void
    {
        $this->addMetadata('geolocation', array_merge($location, [
            'recorded_at' => now()->toISOString(),
        ]));
    }

    /**
     * Ajouter des informations sur l'appareil
     */
    public function addDeviceInfo(array $deviceInfo): void
    {
        $this->addMetadata('device', array_merge($deviceInfo, [
            'recorded_at' => now()->toISOString(),
        ]));
    }

    /**
     * Ajouter des métriques personnalisées
     */
    public function addCustomMetrics(string $metric, mixed $value): void
    {
        $this->addMetadata("metrics.{$metric}", [
            'value' => $value,
            'recorded_at' => now()->toISOString(),
        ]);
    }

    /**
     * Obtenir toutes les ouvertures
     */
    public function getOpenEvents(): array
    {
        return $this->getMetadata('open', []);
    }

    /**
     * Obtenir tous les clics
     */
    public function getClickEvents(): array
    {
        return $this->getMetadata('click', []);
    }

    /**
     * Compter le nombre d'ouvertures
     */
    public function getOpenCount(): int
    {
        return count($this->getOpenEvents());
    }

    /**
     * Compter le nombre de clics
     */
    public function getClickCount(): int
    {
        return count($this->getClickEvents());
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

    /**
     * Scope pour filtrer par métadonnées
     */
    public function scopeWithMetadata($query, string $key, mixed $value = null)
    {
        if ($value === null) {
            return $query->whereJsonContainsKey('metadata', $key);
        }
        
        return $query->whereJsonContains('metadata->' . $key, $value);
    }

    /**
     * Scope pour les emails avec géolocalisation
     */
    public function scopeWithGeolocation($query)
    {
        return $query->whereJsonContainsKey('metadata', 'geolocation');
    }
}