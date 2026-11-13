<?php
header('Content-Type: application/json');
$response = ['success' => false, 'message' => '未知错误'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '无效的请求方法: ' . $_SERVER['REQUEST_METHOD'];
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['file'])) {
    $response['message'] = '未接收到文件';
    $response['debug'] = ['post_keys' => array_keys($_POST), 'files_keys' => array_keys($_FILES)];
    echo json_encode($response);
    exit;
}

if (!isset($_POST['type'])) {
    $response['message'] = '缺少文件类型参数';
    $response['debug'] = ['post_data' => $_POST];
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];
$type = $_POST['type'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 限制',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单限制',
        UPLOAD_ERR_PARTIAL => '文件部分上传',
        UPLOAD_ERR_NO_FILE => '无文件上传',
        UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '写入失败',
        UPLOAD_ERR_EXTENSION => '被扩展中断'
    ];
    $response['message'] = '上传失败: ' . ($errorMessages[$file['error']] ?? '错误码 ' . $file['error']);
    echo json_encode($response);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = [];
switch ($type) {
    case 'image':
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp', 'image/bmp'];
        break;
    case 'video':
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov', 'video/flv'];
        break;
    case 'file':
        $allowedTypes = [];
        break;
    default:
        $response['message'] = '未知的文件类型: ' . $type;
        echo json_encode($response);
        exit;
}

if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
    $response['message'] = '不允许的文件类型: ' . $file['type'] . ' (允许类型: ' . implode(', ', $allowedTypes) . ')';
    echo json_encode($response);
    exit;
}

$fileName = uniqid() . '_' . basename($file['name']);
$fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $url = $protocol . '://' . $host . $scriptPath . '/uploads/' . $fileName;
    
    $response['success'] = true;
    $response['message'] = '上传成功';
    $response['url'] = $url;
} else {
    $response['message'] = '文件保存失败，目录权限可能不足';
}

echo json_encode($response);
exit;