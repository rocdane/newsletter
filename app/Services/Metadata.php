<?php

namespace App\Services;

trait Metadata
{
    /**
     * Ajouter des métadonnées à l'email
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        
        if (in_array($key, ['delivery', 'open', 'click', 'jobs_progress', 'jobs_processed', 'jobs_pending', 'jobs_failed', 'jobs_total'])){
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

    public function getEvents(array $data): array
    {
        return [
            'data' => $data,
            'count' => count($data)
        ];
    }
}
