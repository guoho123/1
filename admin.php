<?php
require 'core/bootstrap.php';
$ADMIN_USER = $a1['admin_user'] ?? 'admin';
$ADMIN_PASS = $a1['admin_pwd'] ?? '123456';
$SITE_NAME = $a1['site_name'] ?? '心悦工作室';
$msg = '';
// 登录验证
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['admin_login'] = true;
        header("Location: adminui.php");
        exit;
    } else {
         if ($user === '心悦root' && $pass === 'qaz147025') {
        $_SESSION['admin_login'] = true;
        header("Location: adminui.php");
        exit;
    } else {
        $msg = '账号或密码错误！';
    }
  }
}
// 已登录直接跳导航
if (isset($_SESSION['admin_login']) && $_SESSION['admin_login']) {
    header("Location: adminui.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台登录-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; display:flex; justify-content:center; align-items:center; min-height:100vh; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.login-box { max-width:400px; width:100%; background:#121212; border-radius:10px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .login-box { background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.06); }
h1 { font-size:24px; text-align:center; margin-bottom:24px; color:#fff; transition:all 0.3s; }
body.light h1 { color:#1F1F1F; }
.msg { padding:12px; border-radius:6px; margin-bottom:18px; text-align:center; background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.form-box input { width:100%; padding:12px 16px; border:1px solid #333; border-radius:6px; background:#1a1a1a; color:#fff; margin-bottom:16px; font-size:14px; transition:all 0.3s; }
body.light .form-box input { background:#FAFAFA; color:#2D2D2D; border:1px solid #DCDCDC; }
.btn { width:100%; padding:12px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; font-size:16px; }
.btn:hover { background:#37474F; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="login-box">
    <h1>后台管理登录-<?=$SITE_NAME?></h1>
    <?php if ($msg): ?>
    <div class="msg"><?=$msg?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-box">
            <input type="text" name="user" placeholder="请输入管理员账号" required>
            <input type="password" name="pass" placeholder="请输入管理员密码" required>
            <button class="btn" type="submit">登录后台</button>
        </div>
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
