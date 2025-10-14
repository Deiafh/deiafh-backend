<?php

namespace App\Enums;

enum NumberType: string
{
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';

    public static function getList(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
