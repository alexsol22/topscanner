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

    public function genera(string $codice): void
    {
        $disco = Storage::disk(config('filesystems.files_disk'));
        $percorso = $this->percorso($codice);

        if ($disco->exists($percorso)) {
            throw new RuntimeException("Il file QR esiste già: {$percorso}");
        }

        $matrice = Encoder::encode($codice, ErrorCorrectionLevel::H())->getMatrix();
        $scala = 16;
        $margine = 4;
        $lato = ($matrice->getWidth() + 2 * $margine) * $scala;
        $immagine = imagecreatetruecolor($lato, $lato + 50);
        $bianco = imagecolorallocate($immagine, 255, 255, 255);
        $nero = imagecolorallocate($immagine, 0, 0, 0);
        imagefill($immagine, 0, 0, $bianco);

        for ($y = 0; $y < $matrice->getHeight(); $y++) {
            for ($x = 0; $x < $matrice->getWidth(); $x++) {
                if ($matrice->get($x, $y) === 1) {
                    $sinistra = ($x + $margine) * $scala;
                    $alto = ($y + $margine) * $scala;
                    imagefilledrectangle($immagine, $sinistra, $alto, $sinistra + $scala - 1, $alto + $scala - 1, $nero);
                }
            }
        }

        $larghezzaTesto = imagefontwidth(5) * strlen($codice);
        $altezzaTesto = imagefontheight(5);
        $testo = imagecreatetruecolor($larghezzaTesto, $altezzaTesto);
        imagefill($testo, 0, 0, imagecolorallocate($testo, 255, 255, 255));
        imagestring($testo, 5, 0, 0, $codice, imagecolorallocate($testo, 0, 0, 0));
        imagecopyresized($immagine, $testo, intdiv($lato - $larghezzaTesto * 2, 2), $lato + 10, 0, 0, $larghezzaTesto * 2, $altezzaTesto * 2, $larghezzaTesto, $altezzaTesto);

        ob_start();
        try {
            if (! imagepng($immagine)) {
                throw new RuntimeException("Impossibile generare l'immagine QR: {$codice}");
            }
            $png = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if ($png === false || ! $disco->put($percorso, $png)) {
            throw new RuntimeException("Impossibile salvare l'immagine QR: {$percorso}");
        }
    }
}
