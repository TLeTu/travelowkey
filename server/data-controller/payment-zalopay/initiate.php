<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../auth.php');
require_once(__DIR__ . '/../connect.php');

// Simple ZaloPay initiation based on IS334 sample
$uid = require_auth();

$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
if ($amount <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid amount']); exit; }

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirectUrl = $scheme . '://' . $host . '/IS207.O11/pages/account/index.html?nav=bill-pane';

$config = [
    'app_id' => getenv('ZLP_APP_ID') ?: 2553,
    'key1' => getenv('ZLP_KEY1') ?: 'PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL',
    'key2' => getenv('ZLP_KEY2') ?: 'kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz',
    'endpoint' => getenv('ZLP_ENDPOINT') ?: 'https://sb-openapi.zalopay.vn/v2/create',
];

$embeddata = json_encode(['redirecturl' => $redirectUrl]);
$items = '[]';
$transID = random_int(0, 1000000);
$order = [
    'app_id' => $config['app_id'],
    'app_time' => round(microtime(true) * 1000),
    'app_trans_id' => date('ymd') . '_' . $transID,
    'app_user' => (string)$uid,
    'item' => $items,
    'embed_data' => $embeddata,
    'amount' => $amount,
    'description' => 'Payment for the order #' . $transID,
    'bank_code' => '',
    'callback_url' => $redirectUrl,
];

$data = $order['app_id'] . '|' . $order['app_trans_id'] . '|' . $order['app_user'] . '|' . $order['amount']
    . '|' . $order['app_time'] . '|' . $order['embed_data'] . '|' . $order['item'];
$order['mac'] = hash_hmac('sha256', $data, $config['key1']);

$context = stream_context_create([
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($order),
    ]
]);

$resp = file_get_contents($config['endpoint'], false, $context);
if ($resp === false) { http_response_code(502); echo json_encode(['error'=>'zalopay unreachable']); exit; }
$result = json_decode($resp, true);
if (!is_array($result) || empty($result['order_url'])) { http_response_code(502); echo json_encode(['error'=>'zalopay error', 'resp'=>$resp]); exit; }

echo json_encode(['redirect' => $result['order_url']]);
