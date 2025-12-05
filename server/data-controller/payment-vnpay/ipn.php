<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../connect.php');
require_once(__DIR__ . '/../payment/config.php');

$cfg = vnp_config();
if (!$cfg['vnp_TmnCode'] || !$cfg['vnp_HashSecret']) { http_response_code(500); echo json_encode(['RspCode' => '99', 'Message' => 'Config error']); exit; }

// Collect GET params from VNP
$params = $_GET;
if (!isset($params['vnp_TxnRef']) || !isset($params['vnp_SecureHash'])) { echo json_encode(['RspCode' => '99', 'Message' => 'Missing params']); exit; }

// Verify signature
$receivedHash = $params['vnp_SecureHash'];
$calcHash = vnp_build_secure_hash($params, $cfg['vnp_HashSecret']);
if (strtolower($receivedHash) !== strtolower($calcHash)) { echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']); exit; }

$txnRef = $params['vnp_TxnRef'];
$responseCode = $params['vnp_ResponseCode'] ?? '';
$bankCode = $params['vnp_BankCode'] ?? null;
$payDate = $params['vnp_PayDate'] ?? null;

// Determine invoice table by probing known tables
$tables = ['flight_invoice', 'bus_invoice', 'room_invoice', 'taxi_invoice'];
$updated = false;
foreach ($tables as $table) {
    $stmt = $conn->prepare("SELECT Id, PaymentStatus FROM $table WHERE Id=?");
    $stmt->bind_param('s', $txnRef); $stmt->execute(); $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['PaymentStatus'] === 'paid') { echo json_encode(['RspCode' => '00', 'Message' => 'OK']); exit; } // idempotent
        if ($row['PaymentStatus'] !== 'pending') { echo json_encode(['RspCode' => '02', 'Message' => 'Invalid status']); exit; }
        if ($responseCode === '00') {
            $upd = $conn->prepare("UPDATE $table SET PaymentStatus='paid', VnpBankCode=?, VnpPayDate=? WHERE Id=?");
            $upd->bind_param('sss', $bankCode, $payDate, $txnRef);
        } else {
            $upd = $conn->prepare("UPDATE $table SET PaymentStatus='failed', VnpBankCode=?, VnpPayDate=? WHERE Id=?");
            $upd->bind_param('sss', $bankCode, $payDate, $txnRef);
        }
        $upd->execute(); $upd->close();
        $updated = true;
        break;
    }
}

if ($updated) { echo json_encode(['RspCode' => '00', 'Message' => 'OK']); }
else { echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']); }
