<?php

namespace App\Enums;

enum WhatsappStato: string
{
    case InAttesa = 'in_attesa';
    case Inviato = 'inviato';
    case Consegnato = 'consegnato';
    case Letto = 'letto';
    case Fallito = 'fallito';

    public function label(): string
    {
        return match ($this) {
            self::InAttesa => 'In attesa',
            self::Inviato => 'Inviato',
            self::Consegnato => 'Consegnato',
            self::Letto => 'Letto',
            self::Fallito => 'Fallito',
        };
    }

    public static function daMeta(string $stato): ?self
    {
        return match ($stato) {
            'accepted', 'sent' => self::Inviato,
            'delivered' => self::Consegnato,
            'read' => self::Letto,
            'failed' => self::Fallito,
            default => null,
        };
    }
}
