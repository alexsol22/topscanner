<?php

namespace App\Models\Legacy;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** Come MyQrCode::_generateCode di Yii: ITALPOL* + 6 cifre, univoco. */
    public static function generaCodice(): string
    {
        do {
            $codice = 'ITALPOL*'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('codice', $codice)->exists());

        return $codice;
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
