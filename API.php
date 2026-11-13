<?php
require 'core/bootstrap.php';
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$act = trim($_POST['act'] ?? '');
$res = ['code' => 200, 'msg' => '操作成功'];
// 全局配置修改
if ($act === 'update_global') {
    $site_name = trim($_POST['site_name'] ?? '');
    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_pwd = trim($_POST['admin_pwd'] ?? '');
    $qq_mail = trim($_POST['qq_mail'] ?? '');
    $port = trim($_POST['port'] ?? '');
    $auth_code = trim($_POST['auth_code'] ?? '');
    if (empty($site_name) || empty($admin_user) || empty($admin_pwd)) {
        $res = ['code'=>400, 'msg'=>'参数不能为空'];
    } else {
        $data = [
            'admin_user' => $admin_user,
            'admin_pwd' => $admin_pwd,
            'site_name' => $site_name,
            'mail_config' => [
                'qq_mail' => $qq_mail,
                'port' => $port,
                'auth_code' => $auth_code
            ]
        ];
        writeJson('a1.json', $data);
    }
}
// 个人网页：上传头像
else if ($act === 'upload_avatar') {
    $a2 = json_decode(file_get_contents('a2.json'), true) ?: [];
    $index = $a2['avatar_index'] ?? 0;
    $newIndex = $index + 1;
    $targetDir = '1/jpg/';
    $file = $_FILES['avatar'] ?? [];
    if (empty($file) || $file['error'] !== 0) {
        $res = ['code'=>400, 'msg'=>'头像上传失败'];
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowExt)) {
            $res = ['code'=>400, 'msg'=>'仅支持jpg/png/gif格式'];
        } else {
            $targetFile = $targetDir . 'avatar' . $newIndex . '.jpg';
            move_uploaded_file($file['tmp_name'], $targetFile);
            $a2['avatar_index'] = $newIndex;
            writeJson('a2.json', $a2);
        }
    }
}
// 个人网页：修改简介
else if ($act === 'update_bio') {
    $bio_list = $_POST['bio_list'] ?? [];
    if (!is_array($bio_list)) {
        $res = ['code'=>400, 'msg'=>'简介格式错误'];
    } else {
        $a2 = json_decode(file_get_contents('a2.json'), true) ?: [];
        $a2['bio_list'] = $bio_list;
        writeJson('a2.json', $a2);
    }
}
// 个人网页：修改链接
else if ($act === 'update_link') {
    $link_list = json_decode($_POST['link_list'] ?? '[]', true);
    if (!is_array($link_list)) {
        $res = ['code'=>400, 'msg'=>'链接格式错误'];
    } else {
        $a2 = json_decode(file_get_contents('a2.json'), true) ?: [];
        $a2['link_list'] = $link_list;
        writeJson('a2.json', $a2);
    }
}



// 发送验证码
else if ($act === 'send_code') {
    $email = trim($_POST['email'] ?? "");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $res = ['code' => 400, 'msg' => '邮箱格式不正确'];
    } else {
        global $MAIL_CONFIG;
        $qq_mail = $MAIL_CONFIG['qq_mail'] ?? "";
        $port = $MAIL_CONFIG['port'] ?? "";
        $auth_code = $MAIL_CONFIG['auth_code'] ?? "";

        if (empty($qq_mail) || empty($port) || empty($auth_code)) {
            $res = ['code' => 500, 'msg' => '管理员未配置邮箱信息'];
        } else {
            $code = rand(100000, 999999);
            session_start();
            $_SESSION['verify_code'] = $code;
            $_SESSION['verify_email'] = $email;
            $_SESSION['verify_expire'] = time() + 300;

            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';
            require 'PHPMailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $smtpDebugLog = '';
            
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) use (&$smtpDebugLog) {
                $smtpDebugLog .= date('[Y-m-d H:i:s] ') . $str . "\n";
            };

            $sendSuccess = false;
            $lastError = '';

            $smtpConfigs = [
                [
                    'port' => 465,
                    'secure' => 'ssl'
                ],
                [
                    'port' => 587,
                    'secure' => 'tls'
                ]
            ];

            foreach ($smtpConfigs as $config) {
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.qq.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $qq_mail;
                    $mail->Password = $auth_code;
                    $mail->Port = $config['port'];
                    $mail->SMTPSecure = $config['secure'];
                    
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $mail->setFrom($qq_mail, '验证码');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = '验证码';
                    $mail->Body = "你的验证码是：<strong>{$code}</strong>，5分钟内有效，请勿泄露给他人。";
                    $mail->AltBody = "你的验证码是：{$code}，5分钟内有效，请勿泄露给他人。";

                    $mail->send();
                    $sendSuccess = true;
                    break;
                } catch (Exception $e) {
                    $lastError = $mail->ErrorInfo;
                    $mail->clearAddresses();
                }
            }

            if ($sendSuccess) {
                $res = ['code' => 200, 'msg' => '验证码发送成功'];
            } else {
                $errorMsg = 'SMTP认证失败，请检查邮箱授权码是否正确';
                if (strpos($lastError, 'Connection refused') !== false) {
                    $errorMsg = 'SMTP连接被拒绝，请检查网络或防火墙设置';
                } else if (strpos($lastError, 'Connection timed out') !== false) {
                    $errorMsg = 'SMTP连接超时，请检查网络连接';
                } else if (strpos($lastError, 'STARTTLS') !== false) {
                    $errorMsg = 'TLS连接失败，请尝试使用SSL方式';
                } else if (strpos($lastError, '535') !== false) {
                    $errorMsg = '邮箱账号或授权码错误，请检查后重试';
                } else if (!empty($lastError)) {
                    $errorMsg = '邮件发送失败: ' . $lastError;
                }
                $res = ['code' => 500, 'msg' => $errorMsg];
            }

            error_log('===== SMTP Debug Log =====');
            error_log($smtpDebugLog);
            error_log('===== End SMTP Debug =====');
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// 插件管理：安装插件
else if ($act === 'plugin_install') {
    $file = $_FILES['plugin_zip'] ?? [];
    if (empty($file) || $file['error'] !== 0) {
        $res = ['code' => 400, 'msg' => '请选择插件压缩包'];
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'zip') {
            $res = ['code' => 400, 'msg' => '仅支持ZIP压缩包'];
        } else {
            require 'core/plugin.php';
            $tempPath = 'data/temp_' . time() . '.zip';
            move_uploaded_file($file['tmp_name'], $tempPath);
            $result = $pluginManager->installPlugin($tempPath);
            unlink($tempPath);
            $res = $result;
        }
    }
}
// 插件管理：启用插件
else if ($act === 'plugin_enable') {
    $pluginName = trim($_POST['plugin_name'] ?? '');
    if (empty($pluginName)) {
        $res = ['code' => 400, 'msg' => '插件名称不能为空'];
    } else {
        $res = $pluginManager->enablePlugin($pluginName);
    }
}
// 插件管理：禁用插件
else if ($act === 'plugin_disable') {
    $pluginName = trim($_POST['plugin_name'] ?? '');
    if (empty($pluginName)) {
        $res = ['code' => 400, 'msg' => '插件名称不能为空'];
    } else {
        $res = $pluginManager->disablePlugin($pluginName);
    }
}
// 插件管理：更新插件
else if ($act === 'plugin_update') {
    $pluginName = trim($_POST['plugin_name'] ?? '');
    $file = $_FILES['update_zip'] ?? [];
    if (empty($pluginName)) {
        $res = ['code' => 400, 'msg' => '插件名称不能为空'];
    } else if (empty($file) || $file['error'] !== 0) {
        $res = ['code' => 400, 'msg' => '请选择新版本压缩包'];
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'zip') {
            $res = ['code' => 400, 'msg' => '仅支持ZIP压缩包'];
        } else {
            $tempPath = 'data/temp_update_' . time() . '.zip';
            move_uploaded_file($file['tmp_name'], $tempPath);
            $result = $pluginManager->updatePlugin($pluginName, $tempPath);
            unlink($tempPath);
            $res = $result;
        }
    }
}
// 插件管理：卸载插件
else if ($act === 'plugin_uninstall') {
    $pluginName = trim($_POST['plugin_name'] ?? '');
    if (empty($pluginName)) {
        $res = ['code' => 400, 'msg' => '插件名称不能为空'];
    } else {
        $res = $pluginManager->uninstallPlugin($pluginName);
    }
}
// 插件管理：获取插件列表
else if ($act === 'plugin_list') {
    $plugins = $pluginManager->getPlugins();
    $res = ['code' => 200, 'msg' => '获取成功', 'data' => $plugins];
}
// 插件API路由处理
else if ($act === 'plugin_api') {
    $route = trim($_POST['route'] ?? '');
    $params = $_POST['params'] ?? [];
    if (!is_array($params)) {
        $params = json_decode($params, true) ?: [];
    }
    $res = $pluginManager->processApiRoute($route, $params);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($res, JSON_UNESCAPED_UNICODE);
exit;


     