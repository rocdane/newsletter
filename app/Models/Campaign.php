<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Bus;
use App\Enums\CampaignStatus;
use App\Services\Metadata; 

class Campaign extends Model
{
    use HasFactory, Metadata;

    protected $fillable = [
        'name',
        'subject',
        'content',
        'from_name',
        'from_email',
        'status',
        'metadata',
        'batch'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    protected static function booted(): void
    {
        static::creating(function ($campaign) {
            if (empty($campaign->metadata)) {
                $campaign->metadata = [];
            }
        });
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', CampaignStatus::COMPLETED->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', CampaignStatus::FAILED->value);
    }

    public function markAsCompleted(): void
    {
        $this->addMetadata('completed', [
            'completed_at' => now()->toISOString()
        ]);

        $this->status = CampaignStatus::COMPLETED->value;
        $this->save();
    }

    public function markAsFailed(): void
    {
        $this->addMetadata('failed', [
            'failed_at' => now()->toISOString()
        ]);

        $this->status = CampaignStatus::FAILED->value;
        $this->save();
    }

    /**
     * Obtenir tous les succès
     */
    public function getCompletedEvents(): array
    {
        $data = $this->getMetadata('completed', []);
        return $this->getEvents($data);
    }

    /**
     * Obtenir tous les échecs
     */
    public function getFailedEvents(): array
    {
        $data = $this->getMetadata('failed', []);
        return $this->getEvents($data);
    }

    /**
     * Met à jour les métadonnées de progression depuis un objet Batch
     */
    public function updateBatchProgress(?string $batchId = null): void
    {
        $batchId = $batchId ?: $this->getMetadata('batch_id');
        
        if (!$batchId) {
            return;
        }

        $batch = Bus::findBatch($batchId);
        
        if (!$batch) {
            $this->addMetadata('batch_status', 'not_found');
            $this->batch_id = null;
            $this->save();
            return;
        }

        // Mettre à jour les métadonnées de progression (tableau pour historique)
        $this->addMetadata('jobs_progress', [
            'progress' => $batch->progress(),
            'updated_at' => now()->toISOString(),
            'batch_id' => $batchId
        ]);

        // Mettre à jour les compteurs (valeurs simples)
        $this->addMetadata('jobs_processed', $batch->processedJobs());
        $this->addMetadata('jobs_pending', $batch->pendingJobs);
        $this->addMetadata('jobs_failed', $batch->failedJobs);
        $this->addMetadata('jobs_total', $batch->totalJobs);

        // Mettre à jour le statut du batch
        $this->addMetadata('batch_status', [
            'status' => $this->getBatchStatus($batch),
            'checked_at' => now()->toISOString(),
            'cancelled' => $batch->cancelled(),
            'finished' => $batch->finished(),
            'has_failures' => $batch->failedJobs > 0
        ]);

        $this->save();
    }

    /**
     * Définit l'ID du batch et met à jour immédiatement la progression
     */
    public function setBatchId(string $batchId): void
    {
        $this->addMetadata('batch_id', $batchId);
        $this->batch_id = $batchId;
        $this->save();
        $this->updateBatchProgress($batchId);
    }

    /**
     * Récupère le statut textuel du batch
     */
    private function getBatchStatus($batch): string
    {
        if ($batch->cancelled()) {
            return 'cancelled';
        }
        
        if ($batch->finished()) {
            return $batch->failedJobs > 0 ? 'completed_with_errors' : 'completed';
        }
        
        return 'processing';
    }

    /**
     * Vérifie si le batch est en cours de traitement
     */
    public function isBatchProcessing(): bool
    {
        $status = $this->getMetadata('batch_status.status');
        return $status === 'processing';
    }

    /**
     * Vérifie si le batch est terminé
     */
    public function isBatchCompleted(): bool
    {
        $status = $this->getMetadata('batch_status.status');
        return in_array($status, ['completed', 'completed_with_errors']);
    }

    /**
     * Scope pour les campagnes avec un batch actif
     */
    public function scopeWithActiveBatch($query)
    {
        return $query->whereNotNull('batch_id')
                    ->whereJsonContains('metadata->batch_status->status', 'processing');
    }

    /**
     * Scope pour les campagnes avec un batch spécifique
     */
    public function scopeWithBatchId($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Vérifie si la campagne a un batch actif
     */
    public function hasActiveBatch(): bool
    {
        return !empty($this->batch_id) && $this->isBatchProcessing();
    }

    /**
     * Nettoie la référence au batch
     */
    public function clearBatchId(): void
    {
        $this->batch_id = null;
        $this->removeMetadata('batch_id');
        $this->save();
    }

    /**
     * Récupère la progression actuelle en pourcentage
     */
    public function getProgressPercentage(): int
    {
        $progressData = $this->getMetadata('jobs_progress', []);
        return $progressData['progress'] ?? 0;
    }

    /**
     * Récupère les statistiques complètes du batch
     */
    public function getBatchStats(): array
    {
        $progressData = $this->getMetadata('jobs_progress', []);
        
        return [
            'progress' => $progressData['progress'] ?? 0,
            'processed' => $this->getMetadata('jobs_processed', 0),
            'pending' => $this->getMetadata('jobs_pending', 0),
            'failed' => $this->getMetadata('jobs_failed', 0),
            'total' => $this->getMetadata('jobs_total', 0),
            'status' => $this->getMetadata('batch_status.status', 'unknown'),
            'last_updated' => $progressData['updated_at'] ?? null
        ];
    }

    /**
     * Récupère les statistiques d'emails
     */
    public function getEmailStats(): array
    {
        $sent = $this->emails()->count();
        $pending = $this->emails()->pending()->count();
        $opened = $this->emails()->opened()->count();
        $clicked = $this->emails()->clicked()->count();
        $delivered = $this->emails()->delivered()->count();

        return [
            'sent' => $sent,
            'pending_count' => $pending,
            'delivered_count' => $delivered,
            'opened_count' => $opened,
            'clicked_count' => $clicked,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0,
        ];
    }
}