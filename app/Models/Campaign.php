<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\CampaignStatus;
use App\Services\Metadata;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'content',
        'from_name',
        'from_email',
        'status',
        'metadata'
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


}
