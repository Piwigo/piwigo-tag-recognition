<?php

/**
 * Live integration test for the OpenAI-compatible backend.
 *
 * Runs against the real llama-server on localhost:8082 and the real Piwigo DB.
 * Not part of the automated PHPUnit suite — run manually for PR validation:
 *
 *   php tests/live_test.php
 */

// ---------------------------------------------------------------------------
// 0.  Helpers
// ---------------------------------------------------------------------------

function hr(string $title = ''): void
{
    $line = str_repeat('─', 70);
    if ($title !== '') {
        echo "\n┌{$line}┐\n│  \e[1m{$title}\e[0m\n└{$line}┘\n";
    } else {
        echo "  {$line}\n";
    }
}

function ok(string $msg):   void { echo "  \e[32m✔\e[0m  {$msg}\n"; }
function fail(string $msg): void { echo "  \e[31m✘\e[0m  {$msg}\n"; }
function info(string $msg): void { echo "  \e[34m→\e[0m  {$msg}\n"; }
function label(string $k, string $v): void { printf("  %-28s %s\n", $k, $v); }

// ---------------------------------------------------------------------------
// 1.  Minimal Piwigo bootstrap
//     We need: DB functions + table constants + IMG_MEDIUM.
//     We skip the full derivative / template / user stack; a thin subclass
//     overrides getFileName() to go straight to the original image file.
// ---------------------------------------------------------------------------

define('PHPWG_ROOT_PATH', '/var/www/piwigo/');
define('TR_PATH', PHPWG_ROOT_PATH . 'plugins/tag_recognition/');

$conf              = [];
$conf['dblayer']   = 'mysqli';
$conf['show_queries'] = false;
$conf['data_location'] = '_data/';
$conf['themes_dir']    = 'themes';

$prefixeTable = 'piwigo_';

// DB layer (pwg_query, pwg_db_real_escape_string, pwg_db_fetch_assoc, …)
include PHPWG_ROOT_PATH . 'include/dblayer/functions_mysqli.inc.php';
pwg_db_connect('localhost', 'piwigo', 'piwigouser', 'piwigo');
pwg_db_check_charset();

// Table name constants (IMAGES_TABLE, TAGS_TABLE, …)
include PHPWG_ROOT_PATH . 'include/constants.php';

// IMG_MEDIUM / IMG_LARGE / … string constants
include PHPWG_ROOT_PATH . 'include/derivative_std_params.inc.php';

// ---------------------------------------------------------------------------
// Stubs for symbols that api_types.php::getFileName() references.
// Our subclass overrides getFileName() entirely, so these are never called
// at runtime — they just need to exist for the class definition to load.
// ---------------------------------------------------------------------------
class SrcImage    { public function __construct(array $info) {} }
class DerivativeImage {
    public static function url(string $type, SrcImage $src): string { return '_data/i/stub'; }
}
function set_make_full_url(): void   {}
function unset_make_full_url(): void {}
function fetchRemote(string $url, &$dest): void { $dest = ''; }
function conf_update_param(string $k, $v, bool $u = false): void {
    global $conf; $conf[$k] = $v;
}
function safe_unserialize(string $s) { return unserialize($s); }

// Plugin source: abstract API class + TR_API_LIST + all three backends
include TR_PATH . 'include/api_types.php';

// ---------------------------------------------------------------------------
// 2.  Live-test subclass
//     Overrides getFileName() to resolve the original gallery image directly
//     from the DB, bypassing derivative-cache machinery that requires a full
//     Piwigo web context.  Every other method (generateTags etc.) is the real
//     production code.
// ---------------------------------------------------------------------------

class LiveTestOpenAICompatible extends OpenAICompatible
{
    /** Max pixel dimension sent to the model. The plugin normally uses the
     *  Piwigo medium derivative (~800 px); we replicate that ceiling here. */
    private const MAX_DIM = 1024;

    /** Temp files created by this instance, cleaned up on __destruct. */
    private array $tmpFiles = [];

    public function __destruct()
    {
        foreach ($this->tmpFiles as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }
    }

    public function getFileName($imageId): string
    {
        $query = '
SELECT path
  FROM ' . IMAGES_TABLE . '
  WHERE id = ' . ((int)$imageId) . '
;';
        $row = pwg_db_fetch_assoc(pwg_query($query));
        if (!$row) {
            throw new Exception("Image {$imageId} not found in database");
        }

        // DB path is like "./galleries/…" — resolve to absolute filesystem path
        $abs = realpath(PHPWG_ROOT_PATH . ltrim($row['path'], './'));
        if ($abs === false || !file_exists($abs)) {
            throw new Exception('Image file not found on disk: ' . $row['path']);
        }

        return $this->maybeScale($abs);
    }

    /**
     * If the image is larger than MAX_DIM on either axis, create a
     * proportionally-scaled JPEG in /tmp and return its path.
     * This mirrors what the plugin normally receives via the medium derivative.
     */
    private function maybeScale(string $path): string
    {
        $size = @getimagesize($path);
        if ($size === false) {
            return $path; // not an image we can inspect; pass through
        }
        [$w, $h] = $size;

        if ($w <= self::MAX_DIM && $h <= self::MAX_DIM) {
            return $path; // already small enough
        }

        $scale  = self::MAX_DIM / max($w, $h);
        $newW   = (int)round($w * $scale);
        $newH   = (int)round($h * $scale);

        $src = match($size[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => false,
        };
        if ($src === false) {
            return $path; // unsupported type; pass through as-is
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        $tmp = tempnam(sys_get_temp_dir(), 'piwigo_live_') . '.jpg';
        imagejpeg($dst, $tmp, 85);
        imagedestroy($dst);

        $this->tmpFiles[] = $tmp;
        return $tmp;
    }
}

// ---------------------------------------------------------------------------
// 3.  Pick a real image from the DB
// ---------------------------------------------------------------------------

hr('1 / 5  Selecting test image from Piwigo database');

$query = '
SELECT id, path, comment
  FROM ' . IMAGES_TABLE . '
  WHERE path IS NOT NULL
    AND path != ""
  ORDER BY id
  LIMIT 20
;';
$result = pwg_query($query);

$testImage = null;
while ($row = pwg_db_fetch_assoc($result)) {
    $abs = realpath(PHPWG_ROOT_PATH . ltrim($row['path'], './'));
    if ($abs !== false && file_exists($abs)) {
        $testImage = $row;
        break;
    }
}

if (!$testImage) {
    fail('No usable image found in the database. Aborting.');
    exit(1);
}

$imageId         = (int) $testImage['id'];
$imagePath       = realpath(PHPWG_ROOT_PATH . ltrim($testImage['path'], './'));
$originalComment = $testImage['comment'] ?? '';

label('Image ID',       (string)$imageId);
label('File path',      $imagePath);
label('File size',      number_format(filesize($imagePath)) . ' bytes');
label('Original comment', $originalComment !== '' ? "\"$originalComment\"" : '(empty)');

// ---------------------------------------------------------------------------
// 4.  Shared configuration
// ---------------------------------------------------------------------------

$MODEL    = 'Qwen3-VL-30B-A3B-Instruct-UD-Q4_K_XL.gguf';
$ENDPOINT = 'http://localhost:8082';

$baseConf = [
    'ENDPOINT'          => $ENDPOINT,
    'API_KEY'           => '',
    'MODEL'             => $MODEL,
    'MAX_TOKENS'        => '500',
    'PROMPT'            => '',
    'WRITE_DESCRIPTION' => '0',
];

$params = [
    'imageId'  => $imageId,
    'language' => 'en',
    'limit'    => 10,
];

$api = new LiveTestOpenAICompatible();

// ---------------------------------------------------------------------------
// Helper: make a raw curl call and return the model's message content string,
// so we can display it before any parsing happens.
// ---------------------------------------------------------------------------
/**
 * Scale $imagePath to at most $maxDim px on the longest axis (using GD) and
 * return the binary JPEG.  Falls back to the original file if GD can't decode
 * it (e.g. unsupported format).
 */
function scaleImageData(string $imagePath, int $maxDim = 1024): string
{
    $data = @file_get_contents($imagePath);
    if ($data === false) { return ''; }

    $size = @getimagesize($imagePath);
    if ($size === false) { return $data; }

    [$w, $h] = $size;
    if ($w <= $maxDim && $h <= $maxDim) { return $data; }

    $scale = $maxDim / max($w, $h);
    $nw    = (int)round($w * $scale);
    $nh    = (int)round($h * $scale);

    $src = match($size[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
        IMAGETYPE_PNG  => imagecreatefrompng($imagePath),
        IMAGETYPE_WEBP => imagecreatefromwebp($imagePath),
        default        => false,
    };
    if ($src === false) { return $data; }

    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    ob_start();
    imagejpeg($dst, null, 85);
    imagedestroy($dst);
    return ob_get_clean();
}

function rawModelCall(string $endpoint, string $model, int $maxTokens,
                      string $prompt, string $imagePath): string
{
    $data = scaleImageData($imagePath);
    if ($data === '') { return '(could not read image file)'; }

    // scaleImageData() always returns JPEG bytes (or the original if no scaling
    // was needed).  Use the original extension's MIME for the data URI.
    $ext     = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                'webp'=>'image/webp','gif'=>'image/gif'];
    $mime    = $mimeMap[$ext] ?? 'image/jpeg';
    // If we scaled (GD always outputs JPEG), override to image/jpeg
    $size    = @getimagesize($imagePath);
    if ($size && max($size[0], $size[1]) > 1024) {
        $mime = 'image/jpeg';
    }
    $dataUri = 'data:' . $mime . ';base64,' . base64_encode($data);

    $payload = [
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'text',      'text'      => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
            ],
        ]],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,           rtrim($endpoint, '/') . '/v1/chat/completions');
    curl_setopt($ch, CURLOPT_POST,          true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT,        180);
    curl_setopt($ch, CURLOPT_HTTPHEADER,    ['Content-Type: application/json',
                                             'Authorization: Bearer none']);
    curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($payload));
    $response = curl_exec($ch);
    if (curl_errno($ch)) { $e = curl_error($ch); curl_close($ch); return "(curl error: $e)"; }
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? "(unexpected response: $response)";
}

// ---------------------------------------------------------------------------
// 5.  TEST A — Default JSON prompt, WRITE_DESCRIPTION = 1
// ---------------------------------------------------------------------------

hr('2 / 5  Test A: default JSON prompt (WRITE_DESCRIPTION = 1)');

$confA = array_merge($baseConf, ['WRITE_DESCRIPTION' => '1']);

// --- 5a. Raw model output (separate call for display) ----------------------
$defaultPrompt = 'Analyze this image and respond with a JSON object containing two keys: '
               . '"description" (a 2-3 sentence description of the image) and "tags" '
               . '(an array of up to 10 relevant keyword tags). Respond with only the '
               . 'JSON object, no markdown or extra text.';

info('Sending image to ' . $ENDPOINT . ' (model: ' . $MODEL . ') …');
$t0        = microtime(true);
$rawContent = rawModelCall($ENDPOINT, $MODEL, 500, $defaultPrompt, $imagePath);
$elapsed   = round(microtime(true) - $t0, 1);

echo "\n";
echo "  \e[33mRaw model response content\e[0m ({$elapsed}s):\n";
echo "  " . str_replace("\n", "\n  ", trim($rawContent)) . "\n\n";

// --- 5b. Run generateTags() (second API call through the plugin code) -------
info('Running generateTags() via plugin code …');
$t0 = microtime(true);
try {
    $tags    = $api->generateTags($confA, $params);
    $elapsed = round(microtime(true) - $t0, 1);
    ok("generateTags() returned in {$elapsed}s");
} catch (Exception $e) {
    fail('generateTags() threw: ' . $e->getMessage());
    exit(1);
}

echo "\n  \e[33mExtracted tags\e[0m:\n";
if (empty($tags)) {
    fail('No tags returned');
} else {
    foreach ($tags as $i => $tag) {
        echo '    ' . ($i + 1) . '. ' . $tag . "\n";
    }
}

// --- 5c. Verify the description was written to the DB ----------------------
echo "\n";
$row = pwg_db_fetch_assoc(pwg_query(
    'SELECT comment FROM ' . IMAGES_TABLE . ' WHERE id = ' . $imageId . ';'
));
$writtenComment = $row['comment'] ?? '';

echo "  \e[33mDescription written to DB\e[0m:\n";
if ($writtenComment !== '' && $writtenComment !== $originalComment) {
    ok("comment field updated");
    echo "  " . wordwrap('"' . $writtenComment . '"', 66, "\n  ", true) . "\n";
} elseif ($writtenComment === '' ) {
    fail("comment field is empty — description was not written");
} else {
    fail("comment field unchanged from original value");
}

// ---------------------------------------------------------------------------
// 6.  TEST B — Custom plain-text prompt (free-text fallback path)
// ---------------------------------------------------------------------------

hr('3 / 5  Test B: custom prompt — free-text fallback path');

$customPrompt = 'List 5 keywords for this image, separated by commas. No JSON, just the keywords.';
$confB = array_merge($baseConf, [
    'PROMPT'            => $customPrompt,
    'WRITE_DESCRIPTION' => '1',
]);

info('Prompt: "' . $customPrompt . '"');
info('Calling ' . $ENDPOINT . ' …');

// Raw output
$t0         = microtime(true);
$rawFreeText = rawModelCall($ENDPOINT, $MODEL, 500, $customPrompt, $imagePath);
$elapsed    = round(microtime(true) - $t0, 1);

echo "\n";
echo "  \e[33mRaw model response content\e[0m ({$elapsed}s):\n";
echo "  " . str_replace("\n", "\n  ", trim($rawFreeText)) . "\n\n";

// Through the plugin
info('Running generateTags() via plugin code …');
$t0 = microtime(true);
try {
    $tagsFallback = $api->generateTags($confB, $params);
    $elapsed      = round(microtime(true) - $t0, 1);
    ok("generateTags() returned in {$elapsed}s");
} catch (Exception $e) {
    fail('generateTags() threw: ' . $e->getMessage());
    exit(1);
}

echo "\n  \e[33mFallback-extracted tags\e[0m:\n";
if (empty($tagsFallback)) {
    fail("Fallback returned no tags — model may have returned a sentence; "
       . "check the raw output above and tune the prompt if needed");
} else {
    foreach ($tagsFallback as $i => $tag) {
        echo '    ' . ($i + 1) . '. ' . $tag . "\n";
    }
}

// Verify description written
$row = pwg_db_fetch_assoc(pwg_query(
    'SELECT comment FROM ' . IMAGES_TABLE . ' WHERE id = ' . $imageId . ';'
));
$commentB = $row['comment'] ?? '';
echo "\n";
if ($commentB !== '' && $commentB !== $writtenComment) {
    ok('DB comment updated with free-text content');
    echo "  " . wordwrap('"' . $commentB . '"', 66, "\n  ", true) . "\n";
} elseif ($commentB === $writtenComment) {
    fail('DB comment was NOT overwritten by Test B (still has Test A value)');
} else {
    fail('DB comment is empty after Test B');
}

// ---------------------------------------------------------------------------
// 7.  Cleanup — restore original comment
// ---------------------------------------------------------------------------

hr('4 / 5  Cleanup — restoring original comment');

$escapedOriginal = pwg_db_real_escape_string($originalComment);
pwg_query(
    'UPDATE ' . IMAGES_TABLE . '
       SET comment = \'' . $escapedOriginal . '\'
     WHERE id = ' . $imageId . ';'
);

$row    = pwg_db_fetch_assoc(pwg_query(
    'SELECT comment FROM ' . IMAGES_TABLE . ' WHERE id = ' . $imageId . ';'
));
$restored = $row['comment'] ?? '';

if ($restored === $originalComment) {
    ok('Original comment restored: ' . ($originalComment !== '' ? '"' . $originalComment . '"' : '(empty)'));
} else {
    fail('Comment restore mismatch — got: "' . $restored . '"');
}

// ---------------------------------------------------------------------------
// 8.  Summary
// ---------------------------------------------------------------------------

hr('5 / 5  Summary');

label('Image tested',      "ID {$imageId}  ({$imagePath})");
label('Model',             $MODEL);
label('Endpoint',          $ENDPOINT);
label('Test A tags',       implode(', ', $tags));
label('Test B tags',       implode(', ', $tagsFallback));
label('DB comment restored', $restored === $originalComment ? 'yes' : 'NO — check manually');

echo "\n";
ok('Live integration test complete');
echo "\n";
