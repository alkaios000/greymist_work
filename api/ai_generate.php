<?php
/**
 * File: api/ai_generate.php
 * Fungsi: Endpoint jembatan menuju app/controllers/ai_action.php untuk Workspace AI Plugin.
 */
$_GET['action'] = $_GET['action'] ?? 'ai_generate_component';
require dirname(__DIR__) . '/index.php';

