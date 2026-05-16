<?php
/**
 * Agent endpoint - serves the PowerShell agent
 * Place this at /api/agent.ps1
 */

// Set proper content type
header('Content-Type: text/plain');

// Read the agent file
$agentPath = dirname(__DIR__) . '/agent.ps1';

if (file_exists($agentPath)) {
    // Plaintext for Debugging
    readfile($agentPath);
} else {
    http_response_code(404);
    echo "404";
}
