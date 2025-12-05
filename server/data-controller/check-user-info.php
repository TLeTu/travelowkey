<?php
require_once('./connect.php');
require_once(__DIR__ . '/auth.php');

$action = isset($_GET['action']) ? $_GET['action'] : null;

if($action == 'check-user-info'){
    // Prefer JWT cookie; fall back to provided userId only if present and no JWT (legacy)
    $uid = get_auth_user_id();
    if (!$uid) {
        $uid = isset($_GET['userId']) ? $_GET['userId'] : null;
    }

    if (!$uid) {
        echo 'no-data';
        exit;
    }

    $uid_safe = $conn->real_escape_string($uid);
    $sql = "SELECT * FROM user WHERE `Id` = '$uid_safe';";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($data = $result->fetch_all(MYSQLI_ASSOC)) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    } else{
        echo 'no-data';
    }

    $conn->close();
}