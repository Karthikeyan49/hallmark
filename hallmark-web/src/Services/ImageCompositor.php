<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use GdImage;
use RuntimeException;

/**
 * Track B — the deterministic compliance-composite engine (GD port of the
 * Python/Pillow reference).
 *
 * Builds the standardised 3-pane deliverable required by the portal:
 *     [ UID IMAGE ] [ MODEL IMAGE ] [ WEIGHT IMAGE ]
 *
 * UID and weight are drawn from verified data (never AI-generated); the model
 * pane comes from {@see AiProvider}. No external service, no per-image cost.
 */
final class ImageCompositor
{
    private const PANE_W = 512;
    private const PANE_H = 512;
    private const MARGIN = 24;
    private const HEADER_H = 96;
    private const FOOTER_H = 56;
    private const LABEL_H = 40;

    // Palette (jewelry-domain, matches the reference build).
    private const WHITE = [255, 255, 255];
    private const INK = [33, 37, 41];
    private const MUTED = [108, 117, 125];
    private const ACCENT = [176, 141, 87];   // muted gold
    private const PANEL = [248, 249, 250];
    private const BORDER = [222, 226, 230];

    private string $fontRegular;
    private string $fontBold;

    public function __construct()
    {
        $fonts = Config::get('fonts', []);
        $this->fontRegular = $fonts['regular'] ?? '';
        $this->fontBold = $fonts['bold'] ?? $this->fontRegular;
        if (!is_file($this->fontRegular)) {
            throw new RuntimeException('TrueType font not found: ' . $this->fontRegular);
        }
    }

    /**
     * Assemble the composite and persist it as a PNG.
     *
     * @param array{uid:string,weight_grams:float,item_type?:string,company?:string,
     *              model_path:string,uid_path?:?string} $spec
     * @return array{filename:string,path:string,track:string,provider:string,
     *               cost_inr:float,billable:bool}
     */
    public function build(array $spec, ?string $provider = null): array
    {
        $spec += ['item_type' => '', 'company' => '', 'uid_path' => null];
        $weightLabel = number_format((float) $spec['weight_grams'], 2) . ' g';
        $dateStr = date('Y-m-d');

        $model = $this->loadImage($spec['model_path']);
        $prov = AiProvider::modelPane($model, $provider);

        [$uidPane, $track] = $this->uidPane($spec);
        $modelPane = $this->modelPane($prov['image'], $spec);
        $weightPane = $this->weightPane($spec, $weightLabel);

        $stripW = 3 * self::PANE_W + 4 * self::MARGIN;
        $stripH = self::PANE_H + 2 * self::MARGIN;
        $totalH = self::HEADER_H + $stripH + self::FOOTER_H;

        $canvas = $this->newCanvas($stripW, $totalH, self::WHITE);

        $this->pasteInto($canvas, $this->header($spec, $stripW, $weightLabel, $dateStr), 0, 0);

        $x = self::MARGIN;
        $y = self::HEADER_H + self::MARGIN;
        foreach ([$uidPane, $modelPane, $weightPane] as $pane) {
            $this->pasteInto($canvas, $pane, $x, $y);
            $x += self::PANE_W + self::MARGIN;
        }

        $note = sprintf(
            'provider=%s • cost=%s INR%s',
            $prov['provider'],
            number_format($prov['cost_inr'], 2),
            $prov['billable'] ? ' • billed to client' : ''
        );
        $this->pasteInto($canvas, $this->footer($note, $stripW), 0, self::HEADER_H + $stripH);

        // Persist.
        $dir = (string) Config::get('output_dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safeUid = preg_replace('/[^A-Za-z0-9\-]/', '_', $spec['uid']) ?: 'item';
        $filename = $safeUid . '-' . bin2hex(random_bytes(4)) . '.png';
        imagepng($canvas, $dir . '/' . $filename);

        return [
            'filename' => $filename,
            'path'     => $dir . '/' . $filename,
            'track'    => $track,
            'provider' => $prov['provider'],
            'cost_inr' => $prov['cost_inr'],
            'billable' => $prov['billable'],
        ];
    }

    // ------------------------------------------------------------------ panes

    /** @return array{0:GdImage,1:string} [panel, track] */
    private function uidPane(array $spec): array
    {
        $panel = $this->paneFrame('UID IMAGE');

        if (!empty($spec['uid_path'])) {
            $img = $this->loadImage($spec['uid_path']);
            $photo = $this->fitWithin($img, self::PANE_W - 24, self::PANE_H - self::LABEL_H - 70, self::PANEL);
            $this->pasteInto($panel, $photo, 12, self::LABEL_H + 12);
            $track = 'B';
        } else {
            $pw = self::PANE_W - 48;
            $ph = self::PANE_H - self::LABEL_H - 90;
            $plate = $this->newCanvas($pw, $ph, [58, 58, 62]);
            for ($yy = 0; $yy < $ph; $yy += 3) {   // brushed-metal hint
                $shade = 58 + ((intdiv($yy, 3) % 2) ? 12 : 0);
                $line = imagecolorallocate($plate, $shade, $shade, $shade + 2);
                imageline($plate, 0, $yy, $pw, $yy, $line);
            }
            $this->textCenter($plate, $spec['uid'], intdiv($pw, 2), intdiv($ph, 2), 30, [236, 236, 240], true);
            $this->pasteInto($panel, $plate, 24, self::LABEL_H + 24);
            $track = 'A';
        }

        $this->textCenter($panel, 'UID: ' . $spec['uid'], intdiv(self::PANE_W, 2), self::PANE_H - 26, 20, self::INK, true);
        return [$panel, $track];
    }

    private function modelPane(GdImage $modelImg, array $spec): GdImage
    {
        $panel = $this->paneFrame('MODEL IMAGE');
        $photo = $this->fitWithin($modelImg, self::PANE_W - 24, self::PANE_H - self::LABEL_H - 60, self::WHITE);
        $this->pasteInto($panel, $photo, 12, self::LABEL_H + 12);
        $caption = $spec['item_type'] !== '' ? $spec['item_type'] : 'Product';
        $this->textCenter($panel, $caption, intdiv(self::PANE_W, 2), self::PANE_H - 26, 20, self::INK, true);
        return $panel;
    }

    private function weightPane(array $spec, string $weightLabel): GdImage
    {
        $panel = $this->paneFrame('WEIGHT IMAGE');
        $cx = intdiv(self::PANE_W, 2);

        $thumb = $this->loadImage($spec['model_path']);
        $thumbFit = $this->fitWithin($thumb, self::PANE_W - 160, self::PANE_H - self::LABEL_H - 230, self::PANEL);
        $this->pasteInto($panel, $thumbFit, $cx - intdiv(imagesx($thumbFit), 2), self::LABEL_H + 24);

        $bodyTop = self::PANE_H - 150;
        $this->filledRoundedRect($panel, 40, $bodyTop, self::PANE_W - 40, self::PANE_H - 30, 14, [52, 58, 64]);

        $panLine = imagecolorallocate($panel, 120, 128, 136);
        imagesetthickness($panel, 3);
        imageline($panel, 70, $bodyTop, self::PANE_W - 70, $bodyTop, $panLine);
        imagesetthickness($panel, 1);

        $lcdW = 220;
        $lcdH = 74;
        $lcdX = $cx - intdiv($lcdW, 2);
        $lcdY = $bodyTop + 30;
        $this->filledRoundedRect($panel, $lcdX, $lcdY, $lcdX + $lcdW, $lcdY + $lcdH, 8, [198, 224, 180]);
        $this->textCenter($panel, $weightLabel, $cx, $lcdY + intdiv($lcdH, 2), 38, [20, 40, 20], true);
        return $panel;
    }

    private function header(array $spec, int $width, string $weightLabel, string $dateStr): GdImage
    {
        $band = $this->newCanvas($width, self::HEADER_H, self::INK);
        $title = $spec['company'] !== '' ? $spec['company'] : 'Hallmarking Compliance Composite';
        $this->textTopLeft($band, $title, self::MARGIN, 20, 26, self::WHITE, true);
        $meta = sprintf('UID %s    |    %s    |    %s', $spec['uid'], $weightLabel, $dateStr);
        $this->textTopLeft($band, $meta, self::MARGIN, 60, 16, [206, 212, 218], false);
        return $band;
    }

    private function footer(string $note, int $width): GdImage
    {
        $band = $this->newCanvas($width, self::FOOTER_H, self::PANEL);
        $border = imagecolorallocate($band, ...self::BORDER);
        imageline($band, 0, 0, $width, 0, $border);
        $this->textTopLeft($band, 'Portal-ready • UID & weight rendered from verified data', self::MARGIN, 18, 14, self::MUTED, false);
        [$w] = $this->measure($note, 14, false);
        $this->textTopLeft($band, $note, $width - self::MARGIN - $w, 18, 14, self::MUTED, false);
        return $band;
    }

    private function paneFrame(string $title): GdImage
    {
        $panel = $this->roundedPanel(self::PANE_W, self::PANE_H, 16, self::PANEL, self::BORDER);
        // Accent label bar with a squared-off bottom edge.
        $this->filledRoundedRect($panel, 0, 0, self::PANE_W - 1, self::LABEL_H, 16, self::ACCENT);
        $accent = imagecolorallocate($panel, ...self::ACCENT);
        imagefilledrectangle($panel, 0, self::LABEL_H - 16, self::PANE_W - 1, self::LABEL_H, $accent);
        $this->textCenter($panel, $title, intdiv(self::PANE_W, 2), intdiv(self::LABEL_H, 2), 18, self::WHITE, true);
        return $panel;
    }

    // --------------------------------------------------------------- GD utils

    private function newCanvas(int $w, int $h, array $bg): GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, ...$bg));
        return $img;
    }

    private function loadImage(string $path): GdImage
    {
        $data = @file_get_contents($path);
        $img = $data !== false ? @imagecreatefromstring($data) : false;
        if ($img === false) {
            throw new RuntimeException('Could not read image: ' . $path);
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        return $img;
    }

    private function fitWithin(GdImage $src, int $boxW, int $boxH, array $bg): GdImage
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = min($boxW / $sw, $boxH / $sh, 1.0);
        $nw = max(1, (int) round($sw * $scale));
        $nh = max(1, (int) round($sh * $scale));

        $canvas = $this->newCanvas($boxW, $boxH, $bg);
        imagecopyresampled($canvas, $src, intdiv($boxW - $nw, 2), intdiv($boxH - $nh, 2), 0, 0, $nw, $nh, $sw, $sh);
        return $canvas;
    }

    private function pasteInto(GdImage $dst, GdImage $src, int $x, int $y): void
    {
        imagecopy($dst, $src, $x, $y, 0, 0, imagesx($src), imagesy($src));
    }

    private function filledRoundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, array $rgb): void
    {
        $c = imagecolorallocate($img, ...$rgb);
        $d = 2 * $r;
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $c);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $c);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $d, $d, $c);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $d, $d, $c);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $d, $d, $c);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $d, $d, $c);
    }

    private function roundedPanel(int $w, int $h, int $radius, array $fill, array $border): GdImage
    {
        $img = $this->newCanvas($w, $h, self::WHITE);
        $this->filledRoundedRect($img, 0, 0, $w - 1, $h - 1, $radius, $border);
        $this->filledRoundedRect($img, 1, 1, $w - 2, $h - 2, $radius - 1, $fill);
        return $img;
    }

    /** @return array{0:int,1:int,2:array} [width, height, bbox] */
    private function measure(string $text, int $size, bool $bold): array
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        $bbox = imagettfbbox($size, 0, $font, $text);
        return [$bbox[2] - $bbox[0], $bbox[1] - $bbox[7], $bbox];
    }

    private function textTopLeft(GdImage $img, string $text, int $x, int $yTop, int $size, array $rgb, bool $bold): void
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        [, , $bbox] = $this->measure($text, $size, $bold);
        $color = imagecolorallocate($img, ...$rgb);
        imagettftext($img, $size, 0, $x - $bbox[0], $yTop - $bbox[7], $color, $font, $text);
    }

    private function textCenter(GdImage $img, string $text, int $cx, int $cy, int $size, array $rgb, bool $bold): void
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        [$w, $h, $bbox] = $this->measure($text, $size, $bold);
        $x = $cx - intdiv($w, 2);
        $yTop = $cy - intdiv($h, 2);
        $color = imagecolorallocate($img, ...$rgb);
        imagettftext($img, $size, 0, $x - $bbox[0], $yTop - $bbox[7], $color, $font, $text);
    }
}
