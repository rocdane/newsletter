<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'content',
        'total_emails',
        'sent_emails',
        'failed_emails',
        'status',
    ];

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_emails === 0) {
            return 0;
        }

        return round(($this->sent_emails + $this->failed_emails) / $this->total_emails * 100, 2);
    }

    public function incrementSent(): void
    {
        $this->increment('sent_emails');
        $this->checkIfCompleted();
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_emails');
        $this->checkIfCompleted();
    }

    private function checkIfCompleted(): void
    {
        if (($this->sent_emails + $this->failed_emails) >= $this->total_emails) {
            $this->update(['status' => 'completed']);
        }
    }
}
