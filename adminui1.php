<?php
require 'core/bootstrap.php';
requireAdmin();
$ADMIN_USER = $a1['admin_user'] ?? 'admin';
$ADMIN_PASS = $a1['admin_pwd'] ?? 'admin';
$MAIL_CONFIG = $a1['mail_config'] ?? ['qq_mail' => '', 'port' => '', 'auth_code' => ''];
$msg = '';
// 接收修改请求，转发到API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'act' => 'update_global',
        'site_name' => trim($_POST['site_name'] ?? ''),
        'admin_user' => trim($_POST['admin_user'] ?? ''),
        'admin_pwd' => trim($_POST['admin_pwd'] ?? ''),
        'qq_mail' => trim($_POST['qq_mail'] ?? ''),
        'port' => trim($_POST['port'] ?? ''),
        'auth_code' => trim($_POST['auth_code'] ?? '')
    ];
    // 调用API
    $ch = curl_init('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'/../API.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $msg = $res['msg'] ?? '操作成功';
    header("Refresh:1;url=adminui1.php");
    if ($res['code'] === 200) {
        header("Refresh:1;url=adminui1.php");
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>全局配置-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; display:flex; justify-content:center; align-items:center; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.form-container { max-width:500px; width:100%; background:#121212; padding:40px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .form-container { background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); }
h1 { text-align:center; margin-bottom:30px; color:#455A64; }
.form-item { margin-bottom:20px; }
label { display:block; margin-bottom:8px; font-size:14px; }
input { width:100%; padding:12px; border:1px solid #333; border-radius:6px; background:#1a1a1a; color:#fff; font-size:14px; transition:all 0.3s; }
body.light input { background:#FAFAFA; color:#2D2D2D; border:1px solid #DCDCDC; }
body.light hr { border-top:1px solid #EAEAEA; }
.btn { width:100%; padding:12px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; font-size:16px; margin-top:10px; }
.btn:hover { background:#37474F; }
.msg { padding:12px; border-radius:6px; margin-bottom:20px; text-align:center; }
.success { background:#E8F5E9; color:#2E7D32; }
.error { background:#FFEBEE; color:#C62828; }
.back { display:block; text-align:center; margin-top:20px; color:#455A64; text-decoration:none; font-size:14px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="form-container">
    <h1>全局配置管理</h1>
    <?php if ($msg): ?>
    <div class="msg <?php echo strpos($msg, '成功')!==false ? 'success' : 'error'; ?>"><?=$msg?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-item">
            <label>网站名称</label>
            <input type="text" name="site_name" value="<?=$SITE_NAME?>" required placeholder="请输入网站名称">
        </div>
        <div class="form-item">
            <label>管理员账号</label>
            <input type="text" name="admin_user" value="<?=$ADMIN_USER?>" required placeholder="请输入管理员账号">
        </div>
        <div class="form-item">
            <label>管理员密码</label>
            <input type="password" name="admin_pwd" value="<?=$ADMIN_PASS?>" required placeholder="请输入管理员密码">
        </div>
        <hr style="margin:30px 0; border:none; border-top:1px solid #333;">
        <h2 style="font-size:16px; color:#455A64; margin-bottom:20px;">邮箱配置（用于发送验证码等功能）</h2>
        <div class="form-item">
            <label>QQ邮箱地址</label>
            <input type="email" name="qq_mail" value="<?=$MAIL_CONFIG['qq_mail']?>" placeholder="例如：xxx@qq.com">
        </div>
        <div class="form-item">
            <label>SMTP端口（通常为465或587）</label>
            <input type="text" name="port" value="<?=$MAIL_CONFIG['port']?>" placeholder="例如：465">
        </div>
        <div class="form-item">
            <label>邮箱授权码</label>
            <input type="password" name="auth_code" value="<?=$MAIL_CONFIG['auth_code']?>" placeholder="在QQ邮箱设置中获取">
        </div>
        <button type="submit" class="btn">保存修改</button>
        <a href="adminui.php" class="back">返回总导航</a>
    </form>
</div>
<script>
// 主题切换
const toggle = document.getElementById('toggle');
if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light');
    toggle.innerText = '切换暗色';
}
toggle.addEventListener('click', () => {
    document.body.classList.toggle('light');
    if (document.body.classList.contains('light')) {
        localStorage.setItem('theme', 'light');
        toggle.innerText = '切换暗色';
    } else {
        localStorage.setItem('theme', 'dark');
        toggle.innerText = '切换亮色';
    }
});
</script>
</body>
</html>
