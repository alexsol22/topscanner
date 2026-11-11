<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QrSede
{
    public function percorso(string $codice): string
    {
        return trim(config('filesystems.private_paths.documenti'), '/').'/QrcodeText/'.str_replace(['/', '\\'], '_', $codice).'.png';
    }

    public function genera(string $codice, string $colore = '#000000', ?string $logo = null, string $titolo = ''): void
    {
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $colore)) {
            throw new RuntimeException('Il colore del QR non è valido.');
        }

        $disco = Storage::disk(config('filesystems.files_disk'));
        $percorso = $this->percorso($codice);

        if ($disco->exists($percorso)) {
            throw new RuntimeException("Il file QR esiste già: {$percorso}");
        }

        $this->scriviPng($codice, $percorso, $colore, $logo, trim($titolo));
    }

    public function generaPersonalizzato(string $codice, string $colore, ?string $logo = null, string $titolo = ''): void
    {
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $colore)) {
            throw new RuntimeException('Il colore del QR non è valido.');
        }

        $percorso = $this->percorso($codice);
        $this->scriviPng($codice, $percorso, $colore, $logo, trim($titolo));
    }

    private function scriviPng(string $codice, string $percorso, string $colore = '#000000', ?string $logo = null, string $titolo = ''): void
    {
        $matrice = Encoder::encode($codice, ErrorCorrectionLevel::H())->getMatrix();
        $scala = 16;
        $margine = 4;
        $lato = ($matrice->getWidth() + 2 * $margine) * $scala;
        $immagine = imagecreatetruecolor($lato, $lato + $this->altezzaDidascalia($titolo));
        $bianco = imagecolorallocate($immagine, 255, 255, 255);
        [$rosso, $verde, $blu] = sscanf($colore, '#%02x%02x%02x');
        $coloreQr = imagecolorallocate($immagine, $rosso, $verde, $blu);
        imagefill($immagine, 0, 0, $bianco);

        for ($y = 0; $y < $matrice->getHeight(); $y++) {
            for ($x = 0; $x < $matrice->getWidth(); $x++) {
                if ($matrice->get($x, $y) === 1) {
                    $sinistra = ($x + $margine) * $scala;
                    $alto = ($y + $margine) * $scala;
                    imagefilledrectangle($immagine, $sinistra, $alto, $sinistra + $scala - 1, $alto + $scala - 1, $coloreQr);
                }
            }
        }

        if ($logo !== null) {
            $immagineLogo = imagecreatefromstring($logo);

            if ($immagineLogo === false) {
                imagedestroy($immagine);
                throw new RuntimeException('Impossibile leggere il logo caricato.');
            }

            $latoLogo = (int) floor($lato * 0.2);
            $logoX = intdiv($lato - $latoLogo, 2);
            $logoY = intdiv($lato - $latoLogo, 2);
            $margineLogo = 10;
            imagefilledrectangle(
                $immagine,
                $logoX - $margineLogo,
                $logoY - $margineLogo,
                $logoX + $latoLogo + $margineLogo,
                $logoY + $latoLogo + $margineLogo,
                $bianco,
            );
            imagecopyresampled(
                $immagine,
                $immagineLogo,
                $logoX,
                $logoY,
                0,
                0,
                $latoLogo,
                $latoLogo,
                imagesx($immagineLogo),
                imagesy($immagineLogo),
            );
            imagedestroy($immagineLogo);
        }

        $this->disegnaDidascalia($immagine, $lato, $coloreQr, $codice, $titolo);

        ob_start();
        try {
            if (! imagepng($immagine)) {
                throw new RuntimeException("Impossibile generare l'immagine QR: {$codice}");
            }
            $png = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($immagine);
        }

        $disco = Storage::disk(config('filesystems.files_disk'));

        if ($png === false || ! $disco->put($percorso, $png)) {
            throw new RuntimeException("Impossibile salvare l'immagine QR: {$percorso}");
        }
    }

    private function altezzaDidascalia(string $titolo): int
    {
        return $titolo === '' ? 56 : 96;
    }

    private function disegnaDidascalia(\GdImage $immagine, int $lato, int $colore, string $codice, string $titolo): void
    {
        $font = resource_path('fonts/DejaVuSans.ttf');
        $righe = $titolo === ''
            ? [['testo' => $codice, 'size' => 18]]
            : [['testo' => $titolo, 'size' => 22], ['testo' => $codice, 'size' => 16]];

        if (! is_file($font)) {
            imagestring($immagine, 5, 8, $lato + 12, $codice, $colore);

            return;
        }

        $y = $lato + 4;

        foreach ($righe as $riga) {
            $box = imagettfbbox($riga['size'], 0, $font, $riga['testo']);
            $larghezza = $box === false ? 0 : abs($box[2] - $box[0]);
            $x = (int) max(0, ($lato - $larghezza) / 2);
            $y += $riga['size'] + 10;
            imagettftext($immagine, $riga['size'], 0, $x, $y, $colore, $font, $riga['testo']);
        }
    }
}
