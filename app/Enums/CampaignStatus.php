<?php

namespace App\Enums;

enum CampaignStatus: String
{
    case DRAFT = 'draft';
    case SENDING = 'sending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return [
            self::DRAFT->value => 'draft',
            self::SENDING->value => 'sending',
            self::COMPLETED->value => 'completed',
            self::FAILED->value => 'failed',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT->value => 'draft',
            self::SENDING->value => 'sending',
            self::COMPLETED->value => 'completed',
            self::FAILED->value => 'failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENDING => 'yellow',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSending(): bool
    {
        return $this === self::SENDING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
