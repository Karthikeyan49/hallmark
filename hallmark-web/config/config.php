<?php
declare(strict_types=1);

/**
 * Application configuration.
 *
 * Values fall back to environment variables so the same code runs a local
 * prototype and a configured deployment without edits.
 */
return [
    'app_name'   => getenv('HALLMARK_APP_NAME') ?: 'Hallmark',
    'db_path'    => BASE_PATH . '/storage/database.sqlite',
    'upload_dir' => BASE_PATH . '/storage/uploads',
    'output_dir' => BASE_PATH . '/storage/output',

    // --- Single admin (prototype). Seeded into the DB on first boot. ---
    'admin_username' => getenv('HALLMARK_ADMIN_USER') ?: 'admin',
    'admin_password' => getenv('HALLMARK_ADMIN_PASS') ?: 'admin123',

    // --- Image pipeline (mirrors the Technical Report) ---
    // Model-pane provider: 'passthrough' (0 INR) | 'flux' (~0.25 INR stub).
    'ai_provider'  => getenv('HALLMARK_AI_PROVIDER') ?: 'passthrough',
    // Per-image cost ceiling in INR. Report requires < 1 INR/image.
    'max_cost_inr' => (float) (getenv('HALLMARK_MAX_COST_INR') ?: 1.0),
    // Bill API cost to the client (Report section 4).
    'bill_client'  => filter_var(getenv('HALLMARK_BILL_CLIENT') ?: 'true', FILTER_VALIDATE_BOOL),
    // Credentials for the paid tier (only needed when ai_provider = 'flux').
    'fal_api_key'  => getenv('FAL_KEY') ?: '',

    // Max upload size for an image (bytes).
    'max_upload_bytes' => 8 * 1024 * 1024,

    'fonts' => [
        'regular' => '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        'bold'    => '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    ],
];
