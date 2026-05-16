<?php
// Marrow C2 - Task Management Class
require_once __DIR__ . '/Database.php';

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function queue($hwid, $module, $args = '')
    {
        return $this->db->insert(
            "INSERT INTO tasks (target_hwid, module_name, command_args, status, created_at) VALUES (?, ?, ?, 'pending', NOW())",
            [$hwid, $module, $args]
        );
    }

    public function getPending($hwid)
    {
        return $this->db->fetch(
            "SELECT * FROM tasks WHERE target_hwid = ? AND status = 'pending' ORDER BY created_at ASC LIMIT 1",
            [$hwid]
        );
    }

    public function complete($taskId, $result)
    {
        return $this->db->execute(
            "UPDATE tasks SET status = 'completed', result = ?, completed_at = NOW() WHERE id = ?",
            [$result, $taskId]
        );
    }

    public function getForTarget($hwid, $limit = 50)
    {
        $limit = (int) $limit; // Cast to int - can't use parameter for LIMIT in MySQL
        return $this->db->fetchAll(
            "SELECT * FROM tasks WHERE target_hwid = ? ORDER BY created_at DESC LIMIT $limit",
            [$hwid]
        );
    }

    public function get($taskId)
    {
        return $this->db->fetch("SELECT * FROM tasks WHERE id = ?", [$taskId]);
    }

    public function getRecent($limit = 20)
    {
        $limit = (int) $limit; // Cast to int
        return $this->db->fetchAll(
            "SELECT t.*, tg.hostname FROM tasks t 
             LEFT JOIN targets tg ON t.target_hwid = tg.hwid 
             ORDER BY t.created_at DESC LIMIT $limit",
            []
        );
    }
}
