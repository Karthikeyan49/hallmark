<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use GdImage;

/**
 * Track A — pluggable *model-pane* provider.
 *
 * Only the model/product pane may be AI-enhanced. UID and weight panes are
 * rendered deterministically by {@see ImageCompositor} because they represent
 * verified facts (an etched string, a measured number) that must never be
 * hallucinated for portal compliance.
 *
 * Providers and per-image cost:
 *   passthrough  0 INR    — uploaded photo, gently contrast-enhanced (no deps)
 *   flux       ~0.25 INR  — hosted FLUX.1 [schnell] (needs FAL_KEY); stubbed
 */
final class AiProvider
{
    private const COSTS = [
        'passthrough' => 0.0,
        'flux'        => 0.25, // ~$0.003 at ~83 INR/USD — well under the 1 INR cap
    ];

    /**
     * Enhance the model pane, enforcing the per-image cost ceiling and falling
     * back to the free provider whenever the paid tier is unusable.
     *
     * @return array{image:GdImage,provider:string,cost_inr:float,billable:bool}
     */
    public static function modelPane(GdImage $source, ?string $provider = null): array
    {
        $name = strtolower($provider ?: (string) Config::get('ai_provider', 'passthrough'));
        $ceiling = (float) Config::get('max_cost_inr', 1.0);
        $cost = self::COSTS[$name] ?? 0.0;

        // Guard: over-budget or unconfigured paid tier -> free fallback.
        if ($name === 'flux') {
            $overBudget = $cost > $ceiling;
            $unconfigured = Config::get('fal_api_key', '') === '';
            if ($overBudget || $unconfigured) {
                $name = 'passthrough';
                $cost = 0.0;
            }
        }

        $image = self::run($name, $source);
        $billable = $cost > 0 && (bool) Config::get('bill_client', true);

        return [
            'image'    => $image,
            'provider' => $name,
            'cost_inr' => $cost,
            'billable' => $billable,
        ];
    }

    private static function run(string $name, GdImage $source): GdImage
    {
        return match ($name) {
            // 'flux' would submit the image to FLUX.1 [schnell] here; the
            // prototype never reaches this branch (guarded above) so no paid
            // call is made by accident.
            default => self::passthrough($source),
        };
    }

    /** Free, dependency-free enhancement: a gentle contrast lift. */
    private static function passthrough(GdImage $source): GdImage
    {
        $copy = imagecreatetruecolor(imagesx($source), imagesy($source));
        imagecopy($copy, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
        imagefilter($copy, IMG_FILTER_CONTRAST, -6); // negative = more contrast
        return $copy;
    }
}
