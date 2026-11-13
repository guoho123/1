<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

file_put_contents('upload_file_debug.log', date('[Y-m-d H:i:s] ') . 'REQUEST_URI=' . $_SERVER['REQUEST_URI'] . ', REQUEST_METHOD=' . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

$response = ['success' => false, 'message' => '未知错误'];

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    $response['message'] = '未登录';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['file'])) {
    $response['message'] = '未接收到文件';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = '上传失败';
    echo json_encode($response);
    exit;
}

$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$uid = uniqid();
$newName = $uid . '.' . $ext;
$uploadPath = $uploadDir . $newName;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $url = '/' . $uploadPath;
    $response['success'] = true;
    $response['message'] = '上传成功';
    $response['url'] = $url;
    $response['uid'] = $uid;
} else {
    $response['message'] = '保存失败';
}

echo json_encode($response);
?>