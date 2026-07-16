<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ComplianceItem;
use App\Services\ImageCompositor;

final class ItemController extends Controller
{
    private const UID_PATTERN = '/^[A-Za-z0-9\-\/]{3,40}$/';
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function index(Request $request): string
    {
        $this->requireAuth();
        return $this->view('items/index', [
            'title' => 'Compliance items',
            'items' => ComplianceItem::all(100),
        ]);
    }

    public function create(Request $request): string
    {
        $this->requireAuth();
        return $this->view('items/new', [
            'title'       => 'New composite',
            'provider'    => Config::get('ai_provider'),
            'maxCost'     => Config::get('max_cost_inr'),
        ]);
    }

    public function store(Request $request): string
    {
        $this->requireAuth();
        $this->requireCsrf($request);

        $uid = (string) $request->input('uid', '');
        $weight = (float) $request->input('weight_grams', '0');
        $itemType = (string) $request->input('item_type', '');
        $company = (string) $request->input('company', '');
        $provider = $request->input('provider') ?: null;

        $errors = [];
        if (!preg_match(self::UID_PATTERN, $uid)) {
            $errors[] = 'UID must be 3–40 characters: letters, digits, - or / only.';
        }
        if ($weight <= 0) {
            $errors[] = 'Weight must be greater than 0.';
        }

        $modelPath = $this->storeUpload($request->file('model_image'), 'model', $errors, true);
        $uidPath = $this->storeUpload($request->file('uid_image'), 'uid', $errors, false);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->cleanup([$modelPath, $uidPath]);
            return $this->redirect('/items/new');
        }

        $result = (new ImageCompositor())->build([
            'uid'          => $uid,
            'weight_grams' => $weight,
            'item_type'    => $itemType,
            'company'      => $company,
            'model_path'   => $modelPath,
            'uid_path'     => $uidPath,
        ], $provider);

        $id = ComplianceItem::create([
            'uid'            => $uid,
            'item_type'      => $itemType,
            'company'        => $company,
            'weight_grams'   => $weight,
            'track'          => $result['track'],
            'provider'       => $result['provider'],
            'cost_inr'       => $result['cost_inr'],
            'billable'       => $result['billable'],
            'composite_file' => $result['filename'],
        ]);

        // Source uploads were only needed to build the composite.
        $this->cleanup([$modelPath, $uidPath]);

        Session::flash('success', 'Composite generated (Track ' . $result['track'] . ').');
        return $this->redirect('/items/' . $id);
    }

    public function show(Request $request, array $params): string
    {
        $this->requireAuth();
        $item = ComplianceItem::find((int) $params['id']);
        if ($item === null) {
            http_response_code(404);
            return $this->view('errors/404', ['title' => 'Not found']);
        }
        return $this->view('items/show', [
            'title' => 'Composite ' . $item['uid'],
            'item'  => $item,
        ]);
    }

    /** Stream the stored composite PNG. */
    public function composite(Request $request, array $params): string
    {
        $this->requireAuth();
        $item = ComplianceItem::find((int) $params['id']);
        if ($item === null) {
            http_response_code(404);
            return 'Not found';
        }
        $path = Config::get('output_dir') . '/' . basename($item['composite_file']);
        if (!is_file($path)) {
            http_response_code(404);
            return 'Composite file missing';
        }
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        return '';
    }

    // ------------------------------------------------------------- internals

    /** Validate + move an uploaded image, returning its stored path or null. */
    private function storeUpload(?array $file, string $prefix, array &$errors, bool $required): ?string
    {
        if ($file === null) {
            if ($required) {
                $errors[] = ucfirst($prefix) . ' image is required.';
            }
            return null;
        }
        if ($file['size'] > (int) Config::get('max_upload_bytes', 8_388_608)) {
            $errors[] = ucfirst($prefix) . ' image is too large.';
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if (!in_array($mime, self::ALLOWED_MIME, true) || getimagesize($file['tmp_name']) === false) {
            $errors[] = ucfirst($prefix) . ' must be a valid image (JPEG/PNG/WebP/GIF).';
            return null;
        }

        $dir = (string) Config::get('upload_dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dest = $dir . '/' . $prefix . '-' . bin2hex(random_bytes(6));
        if (!move_uploaded_file($file['tmp_name'], $dest) && !rename($file['tmp_name'], $dest)) {
            $errors[] = 'Could not save the ' . $prefix . ' upload.';
            return null;
        }
        return $dest;
    }

    /** @param array<int,?string> $paths */
    private function cleanup(array $paths): void
    {
        foreach ($paths as $p) {
            if ($p !== null && is_file($p)) {
                @unlink($p);
            }
        }
    }
}
