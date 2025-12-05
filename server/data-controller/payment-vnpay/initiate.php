<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../auth.php');
require_once(__DIR__ . '/../connect.php');
require_once(__DIR__ . '/../payment/config.php');

$uid = require_auth();
$type = isset($_POST['type']) ? $_POST['type'] : null; // flight|bus|hotel|transfer
$invoiceId = isset($_POST['invoiceId']) ? $_POST['invoiceId'] : null;
if (!$type || !$invoiceId) { http_response_code(400); echo json_encode(['error' => 'missing params']); exit; }

$map = [
  'flight' => ['table' => 'flight_invoice', 'pk' => 'Id', 'fk' => 'Flight_id'],
  'bus' => ['table' => 'bus_invoice', 'pk' => 'Id', 'fk' => 'Bus_id'],
  'hotel' => ['table' => 'room_invoice', 'pk' => 'Id', 'fk' => 'Room_id'],
  'transfer' => ['table' => 'taxi_invoice', 'pk' => 'Id', 'fk' => 'Taxi_id'],
];
if (!isset($map[$type])) { http_response_code(400); echo json_encode(['error' => 'invalid type']); exit; }

$cfg = vnp_config();
if (!$cfg['vnp_TmnCode'] || !$cfg['vnp_HashSecret']) { http_response_code(500); echo json_encode(['error' => 'vnpay not configured']); exit; }

// Load invoice and verify ownership and unpaid status
$table = $map[$type]['table']; $pk = $map[$type]['pk'];
$fk = $map[$type]['fk'];
$stmt = $conn->prepare("SELECT t.$pk AS Id, i.User_id, i.Total AS TotalPrice, t.PaymentStatus FROM $table AS t JOIN invoice AS i ON t.Invoice_id = i.Id WHERE t.$pk = ? OR t.Invoice_id = ? OR t.$fk = ? LIMIT 1");
$stmt->bind_param('sss', $invoiceId, $invoiceId, $invoiceId);
$stmt->execute(); $res = $stmt->get_result();
if ($res->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'invoice not found']); exit; }
$row = $res->fetch_assoc();
if ($row['User_id'] !== $uid) { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }
if ($row['PaymentStatus'] !== 'unpaid') { echo json_encode(['error' => 'invalid status', 'status' => $row['PaymentStatus']]); exit; }

// Prepare VNPAY params
$vnp_TxnRef = $row['Id']; // use line-item id as reference
$vnp_Amount = intval($row['TotalPrice']) * 100; // VNP expects amount x100
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$vnp_CreateDate = date('YmdHis');
$params = [
  'vnp_Version' => $cfg['vnp_Version'],
  'vnp_Command' => $cfg['vnp_Command'],
  'vnp_TmnCode' => $cfg['vnp_TmnCode'],
  'vnp_SecureHashType' => 'SHA512',
  'vnp_Amount' => $vnp_Amount,
  'vnp_CurrCode' => $cfg['vnp_CurrCode'],
  'vnp_TxnRef' => $vnp_TxnRef,
  'vnp_OrderInfo' => $type . ' invoice #' . $vnp_TxnRef,
  'vnp_OrderType' => $type,
  'vnp_ReturnUrl' => $cfg['vnp_ReturnUrl'],
  'vnp_IpAddr' => $vnp_IpAddr,
  'vnp_CreateDate' => $vnp_CreateDate,
  'vnp_Locale' => $cfg['vnp_Locale'],
];
$params['vnp_SecureHash'] = vnp_build_secure_hash($params, $cfg['vnp_HashSecret']);
// Debug: include hash data string for troubleshooting signature issues
$debugHashData = vnp_get_hash_data_string($params);

// Mark invoice pending and store ref
$upd = $conn->prepare("UPDATE $table SET PaymentStatus='pending', PaymentMethod='vnpay', VnpTxnRef=? WHERE $pk=?");
$upd->bind_param('ss', $vnp_TxnRef, $invoiceId); $upd->execute(); $upd->close();

// Build redirect URL
$query = http_build_query($params);
$url = $cfg['vnp_Url'] . '?' . $query;

echo json_encode(['redirect' => $url, 'debug' => ['hashData' => $debugHashData, 'hash' => $params['vnp_SecureHash']]]);
