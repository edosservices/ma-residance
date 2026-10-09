<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\RecognitionDeed;
use Illuminate\Support\Facades\Storage;

final class DeedPdf
{
    public function render(RecognitionDeed $deed, string $copy): string
    {
        $deed->loadMissing('contract.tenant', 'contract.unit.property', 'organization');
        $tenant = $deed->contract->tenant;
        $property = $deed->contract->unit->property;
        $copyLabel = $copy === 'locataire' ? 'Copie locataire' : 'Copie bailleur';
        $identity = $deed->identity_document ?: 'Non fournie';
        $origin = $deed->origin ?: 'Non précisée';
        $when = $deed->certified_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '';

        $lines = [
            ['bold', 'ACTE DE RECONNAISSANCE'],
            ['small', $copyLabel.' · '.$deed->reference],
            ['gap', ''],
            ['text', 'Je soussigné(e) '.$deed->payee_name.', reconnais avoir reçu de '.$tenant->name.' la somme de '.money($deed->amount_minor, $deed->currency).'.'],
            ['text', 'Cette somme couvre '.$deed->deposit_months.' mois de garantie et '.$deed->advance_months.' mois d\'avance, soit '.$deed->months().' mois de loyer.'],
            ['gap', ''],
            ['bold', 'Locataire'],
            ['text', $tenant->name.($tenant->phone ? ' · '.$tenant->phone : '')],
            ['text', 'Origine : '.$origin],
            ['text', 'Pièce d\'identité : '.$identity],
            ['text', 'Lieu de prise de la maison : '.$deed->premises],
            ['text', 'Bien : '.trim(($property->name ?? '').' · '.($deed->contract->unit->name ?? ''))],
            ['gap', ''],
            ['bold', 'Témoins côté bailleur'],
            ['text', $deed->landlord_witnesses ?: 'Aucun témoin nommé'],
            ['bold', 'Témoins côté locataire'],
            ['text', $deed->tenant_witnesses ?: 'Aucun témoin nommé'],
            ['gap', ''],
            ['bold', 'Certification'],
            ['text', 'Acte validé le '.$when.'.'],
            ['text', 'Certificat numérique : '.($deed->certificate_code ?: '—')],
            ['text', 'Signataire : '.($deed->certificate_holder ?: $deed->payee_name)],
            ['text', 'Sceau : '.($deed->content_hash ? substr($deed->content_hash, 0, 16) : '—')],
            ['text', 'Ce sceau confirme que le bailleur a apposé son certificat numérique.'],
        ];

        $image = $this->jpeg($deed->certificate_path);

        return $this->document($lines, $image);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $lines
     * @param  array{bytes: string, width: int, height: int}|null  $image
     */
    private function document(array $lines, ?array $image): string
    {
        $y = 790;
        $stream = "0.059 0.420 0.263 rg\n40 800 515 28 re f\n1 1 1 rg\n";
        $body = '';
        $first = true;

        foreach ($lines as [$kind, $text]) {
            if ($kind === 'gap') {
                $y -= 10;

                continue;
            }

            $size = $kind === 'bold' ? 13 : ($kind === 'small' ? 10 : 11);
            $font = $kind === 'bold' ? 'F1' : 'F2';
            $wrapped = $this->wrap($text, $kind === 'bold' ? 62 : 88);

            foreach ($wrapped as $row) {
                if ($first) {
                    $stream .= "BT /F1 16 Tf 52 808 Td (".$this->win($row).") Tj ET\n";
                    $first = false;
                    $y = 776;

                    continue;
                }

                $color = $kind === 'small' ? '0.35 0.38 0.36 rg' : '0.10 0.13 0.11 rg';
                $body .= $color."\nBT /$font $size Tf 48 $y Td (".$this->win($row).") Tj ET\n";
                $y -= $size + 6;
            }

            $y -= 4;
        }

        if ($image !== null && $y > 120) {
            $body .= "q 120 0 0 48 430 ".max(70, $y - 20)." cm /Im1 Do Q\n";
        }

        $content = $stream.$body;
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        ];
        $resources = '<< /Font << /F1 5 0 R /F2 6 0 R >>'.($image ? ' /XObject << /Im1 7 0 R >>' : '').' >>';
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources $resources >>";
        $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        if ($image !== null) {
            $objects[] = '<< /Type /XObject /Subtype /Image /Width '.$image['width'].' /Height '.$image['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($image['bytes'])." >>\nstream\n".$image['bytes']."\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        return $pdf;
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $next = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($next) > $width && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $next;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function win(string $text): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        $text = $converted === false ? $text : $converted;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * @return array{bytes: string, width: int, height: int}|null
     */
    private function jpeg(?string $path): ?array
    {
        if ($path === null || ! function_exists('imagecreatefromstring') || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('local')->path($path);

        $source = file_get_contents($absolute);
        if ($source === false) {
            return null;
        }

        $image = @imagecreatefromstring($source);
        if ($image === false) {
            return null;
        }

        ob_start();
        imagejpeg($image, null, 80);
        $bytes = ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        return ['bytes' => $bytes, 'width' => $width, 'height' => $height];
    }
}
