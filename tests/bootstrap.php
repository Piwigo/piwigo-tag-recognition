<?php

/**
 * PHPUnit bootstrap — stubs the Piwigo framework so the plugin classes can be
 * loaded and tested without a live Piwigo installation.
 *
 * curl is a real PHP extension and cannot be redefined in pure PHP, so tests
 * that exercise HTTP behaviour point curl at a local PHP built-in server
 * (tests/mock_api_server.php) that returns whatever JSON fixture the test
 * wrote to tests/fixtures/current_response.json.
 */

// ---------------------------------------------------------------------------
// 1.  Piwigo constants required by the plugin source
// ---------------------------------------------------------------------------

define('PHPWG_ROOT_PATH', sys_get_temp_dir() . '/piwigo_test_root/');
define('IMAGES_TABLE',    'piwigo_images');
define('IMAGE_TAG_TABLE', 'piwigo_image_tag');
define('TAGS_TABLE',      'piwigo_tags');
define('IMG_MEDIUM',      'me');   // arbitrary value; only passed through DerivativeImage::url()
define('TR_PATH',         dirname(__DIR__) . '/');

// ---------------------------------------------------------------------------
// 2.  Global state shared between the plugin stubs and test assertions
// ---------------------------------------------------------------------------

$GLOBALS['pwg_queries'] = [];   // every pwg_query() call appends here
$GLOBALS['pwg_conf']    = [];

// ---------------------------------------------------------------------------
// 3.  Piwigo function stubs
// ---------------------------------------------------------------------------

function pwg_query(string $query)
{
    $GLOBALS['pwg_queries'][] = $query;
    return true;
}

function pwg_db_fetch_assoc($result): array
{
    return ['id' => 1, 'path' => 'test.jpg', 'representative_ext' => null];
}

function pwg_db_real_escape_string(string $str): string
{
    // Mirrors MySQL escaping closely enough for assertion purposes
    return addslashes($str);
}

function query2array(string $query, $key = null, $col = null): array
{
    return [];
}

function conf_update_param(string $key, $value, bool $update = false): void
{
    $GLOBALS['pwg_conf'][$key] = $value;
}

function safe_unserialize(string $str)
{
    return unserialize($str);
}

function set_make_full_url(): void   {}
function unset_make_full_url(): void {}
function fetchRemote(string $url, &$dest): void { $dest = ''; }

// ---------------------------------------------------------------------------
// 4.  Piwigo class stubs required by API::getFileName()
//     TestableOpenAICompatible overrides getFileName() entirely, so these
//     stubs only need to satisfy the abstract class at load time.
// ---------------------------------------------------------------------------

class SrcImage
{
    public function __construct(array $info) {}
}

class DerivativeImage
{
    public static function url(string $type, SrcImage $src): string
    {
        // Must NOT start with "i" to avoid the cache-generation branch
        return '_data/i/stub_derivative.jpg';
    }
}

// ---------------------------------------------------------------------------
// 5.  Tiny test-image fixture
//     Any readable file works; content is base64-encoded and sent to the mock
//     server which ignores it.  The .jpg extension drives MIME-type selection.
// ---------------------------------------------------------------------------

$fixturesDir   = __DIR__ . '/fixtures';
$testImagePath = $fixturesDir . '/test_image.jpg';

if (!is_dir($fixturesDir)) {
    mkdir($fixturesDir, 0755, true);
}

if (!file_exists($testImagePath)) {
    file_put_contents($testImagePath, 'FAKE_JPEG_CONTENT_FOR_TESTS');
}

// ---------------------------------------------------------------------------
// 6.  Start the mock HTTP server (PHP built-in server)
// ---------------------------------------------------------------------------

define('MOCK_SERVER_PORT', 17890);

$GLOBALS['mock_server_proc']      = null;
$GLOBALS['mock_server_available'] = false;

$serverScript = __DIR__ . '/mock_api_server.php';

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', '/dev/null', 'w'],
    2 => ['file', '/dev/null', 'w'],
];

$proc = proc_open(
    PHP_BINARY . ' -S 127.0.0.1:' . MOCK_SERVER_PORT . ' ' . escapeshellarg($serverScript),
    $descriptors,
    $pipes
);

if (is_resource($proc)) {
    $GLOBALS['mock_server_proc'] = $proc;
    fclose($pipes[0]);

    // Wait up to 3 s for the server to accept connections
    $deadline = microtime(true) + 3.0;
    while (microtime(true) < $deadline) {
        $sock = @fsockopen('127.0.0.1', MOCK_SERVER_PORT, $errno, $errstr, 0.1);
        if ($sock !== false) {
            fclose($sock);
            $GLOBALS['mock_server_available'] = true;
            break;
        }
        usleep(50_000); // 50 ms
    }
}

register_shutdown_function(static function (): void {
    if (isset($GLOBALS['mock_server_proc']) && is_resource($GLOBALS['mock_server_proc'])) {
        proc_terminate($GLOBALS['mock_server_proc']);
        proc_close($GLOBALS['mock_server_proc']);
    }
    // Clean up the fixture file written per-test
    $f = __DIR__ . '/fixtures/current_response.json';
    if (file_exists($f)) {
        unlink($f);
    }
});

// ---------------------------------------------------------------------------
// 7.  Load plugin source
//     api_types.php defines TR_API_LIST, the abstract API class, and
//     auto-includes all three API class files via TR_PATH.
// ---------------------------------------------------------------------------

include_once dirname(__DIR__) . '/include/api_types.php';
