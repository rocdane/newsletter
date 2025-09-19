<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\CampaignStatus;

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
    ];

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
}
