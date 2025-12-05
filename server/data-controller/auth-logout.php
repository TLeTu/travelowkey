<?php
require_once(__DIR__ . '/auth.php');
header('Content-Type: application/json');

clear_auth_cookie();
echo json_encode(['ok' => true]);
