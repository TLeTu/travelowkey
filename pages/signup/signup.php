<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../server/data-controller/connect.php');

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'];
$phone = $data['phone'];
$passwordInput = $data['password'];

$sql = "SELECT * FROM user WHERE Email = ? OR Phone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Email or phone already exists.'));
} else {
    $unique_id = uniqid('U');
    $unique_passport_id = uniqid('PP');

    $passport_sql = "INSERT INTO passport (Id) VALUES (?)";
    $passport_stmt = $conn->prepare($passport_sql);
    $passport_stmt->bind_param("s", $unique_passport_id);

    if ($passport_stmt->execute()) {
        // Hash password (PASSWORD_DEFAULT)
        $hashed = password_hash($passwordInput, PASSWORD_DEFAULT);

        $sql = "INSERT INTO user (Id, Email, Phone, Password, Passport_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $unique_id, $email, $phone, $hashed, $unique_passport_id);
    
        if ($stmt->execute()) {
            echo json_encode(array('status' => 'success', 'userId' => $unique_id));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Error: ' . $stmt->error));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Error: ' . $passport_stmt->error));
    }
    $passport_stmt->close();
}
$stmt->close();
$conn->close();