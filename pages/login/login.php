<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../server/data-controller/auth.php');

// Database connection (centralized credentials)
require_once(__DIR__ . '/../../server/data-controller/connect.php');

// Get data from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$emailOrPhone = $data['emailOrPhone'];
$passwordInput = $data['password'];

// Check if the user exists in the database
$sql = "SELECT * FROM user WHERE Email = ? OR Phone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
$stmt->execute();
$result = $stmt->get_result();
$userExists = $result->num_rows > 0;

if ($userExists) {
    $row = $result->fetch_assoc();
    $storedPassword = $row['Password'];

    $passwordIsCorrect = false;
    // Try verifying hashed password first
    if (password_get_info($storedPassword)['algo'] !== 0) {
        $passwordIsCorrect = password_verify($passwordInput, $storedPassword);
    } else {
        // Legacy plaintext fallback
        $passwordIsCorrect = ($storedPassword === $passwordInput);
    }

    if ($passwordIsCorrect) {
        // If legacy, migrate to hashed
        if (password_get_info($storedPassword)['algo'] === 0) {
            $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE user SET Password = ? WHERE Id = ?");
            $upd->bind_param("ss", $newHash, $row['Id']);
            $upd->execute();
            $upd->close();
        } elseif (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE user SET Password = ? WHERE Id = ?");
            $upd->bind_param("ss", $newHash, $row['Id']);
            $upd->execute();
            $upd->close();
        }

        // Issue JWT auth cookie
        $jwt = sign_jwt(['sub' => $row['Id']]);
        set_auth_cookie($jwt);

        echo json_encode(array('success' => true, 'userId' => $row['Id']));
    } else {
        echo json_encode(array('success' => false));
    }
} else {
    // User does not exist, return error message
    echo json_encode(array('success' => false));
}
$stmt->close();
$conn->close();