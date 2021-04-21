<?php
declare(strict_types=1);

/**
 * @author Henrik Gebauer <mensa@henrik-gebauer.de>
 * @license https://creativecommons.org/publicdomain/zero/1.0/ CC0 1.0
 */

/**
 * Ändert die Größe eines Bildes unter Beibehaltung des Seitenverhältnisses
 *
 * @param string $sourcePath Quell-Pfad
 * @param string $destinationPath Ziel-Pfad
 * @param string $sourceType Dateityp ("png" oder "jpeg")
 * @param string $destinationType Dateityp ("png" oder "jpeg")
 * @param int $maxWidth
 * @param int $maxHeight
 * @return array(int, int) Breite und Höhe des Bildes
 * @throws \OutOfBoundsException wenn $sourceType oder $destinationType ungültige Werte haben
 */
function resizeImage(string $sourcePath, string $destinationPath, string $sourceType, $destinationType, int $maxWidth, int $maxHeight)
{
    switch ($sourceType) {
        case 'png':
            $im = imageCreateFromPng($sourcePath);
            break;
        case 'jpeg':
            $im = imageCreateFromJPEG($sourcePath);
            break;
        default:
            throw new \OutOfBoundsException('Unknown image type: ' . $sourceType, 1506377405);
    }

    // Groesse festlegen
    $width = min(imageSX($im), $maxWidth);
    $height = (int)round(min($maxHeight, imageSY($im) * $width / imageSX($im)));
    $width = (int)round(min($maxWidth, imageSX($im) * $height / imageSY($im)));

    // neues, transparentes Bild erstellen
    $new = imageCreateTrueColor($width, $height);
    imageAlphaBlending($new, false);
    imageSaveAlpha($new, true);
    $transparent = imageColorAllocateAlpha($new, 255, 255, 255, 127);
    imageFilledRectangle($new, 0, 0, $width, $height, $transparent);

    imageCopyResampled($new, $im, 0, 0, 0, 0, $width, $height, imageSX($im), imageSY($im));

    switch ($destinationType) {
        case 'png':
            imagePng($new, $destinationPath);
            break;
        case 'jpeg':
            imageJpeg($new, $destinationPath);
            break;
        default:
            throw new \OutOfBoundsException('Unknown image type: ' . $destinationType, 1506377417);
    }

    return [$width, $height];
}
