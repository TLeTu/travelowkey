<?php
require_once(__DIR__ . '/../../server/data-controller/connect.php');
require_once(__DIR__ . '/../../server/data-controller/payment/config.php');

$cfg = vnp_config();
$params = $_GET;
$txnRef = $params['vnp_TxnRef'] ?? null;
$secureHash = $params['vnp_SecureHash'] ?? null;
$responseCode = $params['vnp_ResponseCode'] ?? null;
$bankCode = $params['vnp_BankCode'] ?? null;
$payDate = $params['vnp_PayDate'] ?? null;

$valid = false;
if ($txnRef && $secureHash) {
  $calcHash = vnp_build_secure_hash($params, $cfg['vnp_HashSecret']);
  $valid = (strtolower($secureHash) === strtolower($calcHash));
}

$statusText = 'Payment failed';
if ($valid && $responseCode === '00') { $statusText = 'Payment successful'; }

// Try to update invoice to paid/failed if still pending (defensive)
if ($txnRef) {
  $tables = ['flight_invoice', 'bus_invoice', 'room_invoice', 'taxi_invoice'];
  foreach ($tables as $table) {
    $stmt = $conn->prepare("SELECT Id, PaymentStatus FROM $table WHERE Id=?");
    $stmt->bind_param('s', $txnRef); $stmt->execute(); $res = $stmt->get_result();
    if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      if ($row['PaymentStatus'] === 'pending') {
        if ($valid && $responseCode === '00') {
          $upd = $conn->prepare("UPDATE $table SET PaymentStatus='paid', VnpBankCode=?, VnpPayDate=? WHERE Id=?");
          $upd->bind_param('sss', $bankCode, $payDate, $txnRef);
        } else {
          $upd = $conn->prepare("UPDATE $table SET PaymentStatus='failed', VnpBankCode=?, VnpPayDate=? WHERE Id=?");
          $upd->bind_param('sss', $bankCode, $payDate, $txnRef);
        }
        $upd->execute(); $upd->close();
      }
      break;
    }
  }
}

// Simple HTML response
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>VNPAY Payment Status</title>
  <style>
    body { font-family: sans-serif; max-width: 680px; margin: 2rem auto; }
    .status { font-size: 1.25rem; margin-bottom: 1rem; }
    .actions a { display: inline-block; margin-right: 1rem; }
  </style>
</head>
<body>
  <div class="status"><?php echo htmlspecialchars($statusText); ?></div>
  <div>Mã giao dịch: <?php echo htmlspecialchars($txnRef ?: 'N/A'); ?></div>
  <div>Ngân hàng: <?php echo htmlspecialchars($bankCode ?: 'N/A'); ?></div>
  <div>Thời gian: <?php echo htmlspecialchars($payDate ?: 'N/A'); ?></div>
  <hr />
  <div class="actions">
    <a href="../account/">Về tài khoản</a>
    <a href="../main/">Trang chính</a>
  </div>
</body>
</html>
