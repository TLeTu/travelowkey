<?php
// VNPAY configuration helper. Read from environment; provide sane defaults for dev.
function vnp_config() {
    $tmnCode = getenv('VNP_TMN_CODE') ?: '';
    $hashSecret = getenv('VNP_HASH_SECRET') ?: '';
    $vnpUrl = getenv('VNP_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    $returnUrl = getenv('VNP_RETURN_URL') ?: (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST'] . '/IS207.O11/pages/payment-callback/index.php') : 'http://localhost/IS207.O11/pages/payment-callback/index.php');
    return [
        'vnp_Version' => '2.1.0',
        'vnp_Command' => 'pay',
        'vnp_TmnCode' => $tmnCode,
        'vnp_HashSecret' => $hashSecret,
        'vnp_Url' => $vnpUrl,
        'vnp_ReturnUrl' => $returnUrl,
        'vnp_Locale' => 'vn',
        'vnp_CurrCode' => 'VND',
    ];
}

function vnp_build_secure_hash($params, $hashSecret) {
    // Per VNPAY doc: sort keys, build key=value pairs joined by '&' using raw values
    // Exclude vnp_SecureHash and vnp_SecureHashType from hash input
    ksort($params);
    $hashData = '';
    foreach ($params as $key => $value) {
        if ($key === 'vnp_SecureHash' || $key === 'vnp_SecureHashType') continue;
        if ($value === null || $value === '') continue;
        $hashData .= ($hashData ? '&' : '') . $key . '=' . $value;
    }
    return hash_hmac('sha512', $hashData, $hashSecret);
}

function vnp_get_hash_data_string($params) {
    ksort($params);
    $hashData = '';
    foreach ($params as $key => $value) {
        if ($key === 'vnp_SecureHash' || $key === 'vnp_SecureHashType') continue;
        if ($value === null || $value === '') continue;
        $hashData .= ($hashData ? '&' : '') . $key . '=' . $value;
    }
    return $hashData;
}
