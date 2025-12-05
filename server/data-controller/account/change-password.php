<?php
require_once('../connect.php');
require_once(__DIR__ . '/../auth.php');

$action = $_POST['action'];

if($action == 'change-password'){
    $userId = require_auth();
    $newPassword = $_POST['newPassword'];

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET `Password` = ? WHERE `Id` = ?");
    $stmt->bind_param("ss", $newHash, $userId);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "fail";
    }
    $stmt->close();
    $conn->close();
}


if($action == 'check-old-password'){
    $userId = require_auth();
    $oldPassword = $_POST['oldPassword'];

    $stmt = $conn->prepare("SELECT `Password` FROM user WHERE `Id` = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $stored = $row['Password'];
        $ok = false;
        if (password_get_info($stored)['algo'] !== 0) {
            $ok = password_verify($oldPassword, $stored);
        } else {
            $ok = ($stored === $oldPassword);
        }
        echo $ok ? 'success' : 'fail';
    } else {
        echo 'fail';
    }
    $stmt->close();
    $conn->close();
}