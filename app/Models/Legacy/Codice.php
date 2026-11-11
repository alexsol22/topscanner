<?php

namespace App\Models\Legacy;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Codice extends LegacyModel
{
    protected $table = 'tc_codici';

    public const SEDE = 'S';

    public const POSTAZIONE = 'P';

    public const CREATO = 'C';

    public const ATTIVO = 'A';

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }

    public function postazione(): BelongsTo
    {
        return $this->belongsTo(Postazione::class, 'id_postazione');
    }

    public function svg(int $size = 200): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($this->codice);
    }

    /**
     * Prossimo ITALPOL* a 6 cifre, in coda alla numerazione già usata.
     * I codici non numerici (per esempio ITALPOL*S12) non contano.
     * I riservati sono numeri scelti in questa richiesta ma non ancora scritti.
     *
     * @param  array<int, string>  $riservati
     */
    public static function prossimoCodice(array $riservati = []): string
    {
        $numero = static::ultimoNumero($riservati);

        do {
            $numero++;

            if ($numero > 999999) {
                throw new RuntimeException('Numerazione QR esaurita.');
            }

            $codice = 'ITALPOL*'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
        } while (in_array($codice, $riservati, true) || static::query()->where('codice', $codice)->exists());

        return $codice;
    }

    /** @param  array<int, string>  $riservati */
    private static function ultimoNumero(array $riservati): int
    {
        $massimo = 0;

        foreach (static::query()->pluck('codice')->merge($riservati) as $codice) {
            if (preg_match('/^ITALPOL\*(\d{6})$/', (string) $codice, $corrispondenza) === 1) {
                $massimo = max($massimo, (int) $corrispondenza[1]);
            }
        }

        return $massimo;
    }

    /** Equivale a generateCodes(1) + associaCodice di Yii: la riga nasce 'C' e viene subito associata ('A'). */
    public static function nuovo(string $tipo, int $sedeId, ?int $postazioneId, string $codice): self
    {
        return static::create([
            'codice' => $codice,
            'stato' => self::ATTIVO,
            'tipo' => $tipo,
            'id_sede' => $sedeId,
            'id_postazione' => $postazioneId,
        ]);
    }
}
