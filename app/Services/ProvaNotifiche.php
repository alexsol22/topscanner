<?php

namespace App\Services;

use App\Models\Legacy\Admin;
use App\Models\Legacy\Cliente;
use RuntimeException;

class ProvaNotifiche
{
    public const EMAIL = 'alesolax55@gmail.com';

    public const CLIENTE = 'prova';

    /**
     * La prova è il comportamento predefinito finché NOTIFICHE_PROVA è attivo, anche senza --prova-sicura.
     *
     * @return array{operatore: int, cliente: int}|null
     */
    public function contesto(Comunicazioni $comunicazioni, bool $forzata = false): ?array
    {
        if (! $forzata && config('notifiche.prova') !== true) {
            return null;
        }

        return $this->attiva($comunicazioni);
    }

    /**
     * @return array{operatore: int, cliente: int}
     */
    public function attiva(Comunicazioni $comunicazioni): array
    {
        $operatori = Admin::query()->whereRaw('LOWER(email) = ?', [self::EMAIL])->limit(2)->get();

        if ($operatori->count() !== 1) {
            throw new RuntimeException('Prova interrotta: deve esistere esattamente un operatore con l’indirizzo di test.');
        }

        $clienti = Cliente::query()->whereRaw('LOWER(TRIM(ragione_sociale)) = ?', [self::CLIENTE])->limit(2)->get();

        if ($clienti->count() !== 1) {
            throw new RuntimeException('Prova interrotta: deve esistere esattamente un cliente «prova».');
        }

        $comunicazioni->attivaProva(self::EMAIL);

        return [
            'operatore' => (int) $operatori->first()->id,
            'cliente' => (int) $clienti->first()->id,
        ];
    }
}
