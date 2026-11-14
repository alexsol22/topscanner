<?php

namespace App\Models\Legacy;

use App\Enums\WhatsappStato;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendWhatsapp extends LegacyModel
{
    protected $table = 'tc_send_whatsapp';

    protected function casts(): array
    {
        return [
            'parametri' => 'array',
            'stato' => WhatsappStato::class,
            'data_invio' => 'datetime',
            'aggiornato_il' => 'datetime',
        ];
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'id_operatore');
    }
}
