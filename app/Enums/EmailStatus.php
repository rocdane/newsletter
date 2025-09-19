<?php

namespace App\Enums;

enum EmailStatus: String
{
    case PENDING = 'pending';
    case DELIVERED = 'delivered';
    case CLICKED = 'clicked';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return [
            self::PENDING->value => 'pending',
            self::DELIVERED->value => 'delivered',
            self::CLICKED->value => 'clicked',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING->value => 'pending',
            self::DELIVERED->value => 'delivered',
            self::CLICKED->value => 'clicked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::DELIVERED => 'yellow',
            self::CLICKED => 'green',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isClicked(): bool
    {
        return $this === self::CLICKED;
    }
}
