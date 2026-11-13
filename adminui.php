<?php
require 'core/bootstrap.php';
requireAdmin();
if (isset($_GET['act']) && $_GET['act'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台总管理-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; display:flex; justify-content:center; align-items:center; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:600px; width:100%; background:#121212; border-radius:12px; padding:40px; box-shadow:0 2px 8px rgba(0,0,0,0.5); text-align:center; transition:all 0.3s; }
body.light .container { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
h1 { font-size:28px; color:#fff; margin-bottom:40px; transition:all 0.3s; }
body.light h1 { color:#1F1F1F; }
.nav-box { display:grid;  gap:20px; margin-bottom:40px; }
.nav-card { padding:30px 20px; border:1px solid #333; border-radius:10px; cursor:pointer; transition:all 0.3s; text-decoration:none; color:#fff; background:#1a1a1a; }
body.light .nav-card { color:#2D2D2D; border:1px solid #EAEAEA; background:#fff; }
.nav-card:hover { border-color:#455A64; background:#222; transform:translateY(-4px); box-shadow:0 4px 12px rgba(69,90,100,0.3); }
body.light .nav-card:hover { background:#F8F9FA; box-shadow:0 4px 12px rgba(69,90,100,0.1); }
.nav-card .icon { display:inline-block; width:40px; height:40px; line-height:40px; border-radius:50%; background:#455A64; color:#fff; font-size:20px; margin-bottom:16px; }
.nav-card h2 { font-size:18px; margin-bottom:8px; }
.nav-card p { font-size:14px; color:#888; }
body.light .nav-card p { color:#666; }
.foot { display:flex; justify-content:space-between; align-items:center; padding-top:20px; border-top:1px solid #333; transition:all 0.3s; }
body.light .foot { border-top:1px solid #EAEAEA; }
.btn { padding:8px 16px; border:none; border-radius:6px; background:#D32F2F; color:#fff; cursor:pointer; text-decoration:none; font-size:14px; }
.btn:hover { background:#B71C1C; }
.back { color:#3949AB; text-decoration:none; font-size:14px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
/* 移动端适配 */
@media (max-width: 480px) {
    .nav-box { grid-template-columns:1fr; }
}
</style>
<script charset="UTF-8" id="LA_COLLECT" src="//sdk.51.la/js-sdk-pro.min.js"></script>
<script>LA.init({id:"3IYyDZxL1GcYl66S",ck:"3IYyDZxL1GcYl66S"})</script>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <h1>后台管理总导航-<?=$SITE_NAME?></h1>
    <div class="nav-box">
    <a href="adminui1.php" class="nav-card">
        <span class="icon">⚙️</span>
        <h2>全局配置管理</h2>
        <p>修改网站名、后台账号密码</p>
    </a>
    <?php
    // 装有且启用「小屋玻璃主题」插件时显示主题设置入口，否则显示默认个人主页管理
    $xfGlassEnabled = false;
    foreach ($pluginManager->getPlugins() as $p) {
        if ($p['name'] === 'xf_glass_theme' && $p['enabled']) { $xfGlassEnabled = true; break; }
    }
    if ($xfGlassEnabled): ?>
    <a href="adminui_plugins.php?plugin=xf_glass_theme&page=admin" class="nav-card">
        <span class="icon">🏡</span>
        <h2>小屋玻璃主题设置</h2>
        <p>前台主题设置、社交按钮、项目与文章管理</p>
    </a>
    <?php else: ?>
    <a href="adminui2.php" class="nav-card">
        <span class="icon">👤</span>
        <h2>个人主页管理</h2>
        <p>头像、简介、外链管理</p>
    </a>
    <?php endif; ?>
    <?php echo $pluginManager->triggerHook('admin_nav'); ?>
    <a href="adminui_plugins.php" class="nav-card">
        <span class="icon">🔌</span>
        <h2>插件管理中心</h2>
        <p>安装、启用、更新、卸载插件</p>
    </a>
    <a href="index.php" target="_blank" class="nav-card">
        <span class="icon">👁️</span>
        <h2>预览个人主页</h2>
        <p>新窗口打开，查看最新效果</p>
    </a>
</div>

    <div class="foot">
        <a href="adminui.php" class="back">刷新页面</a>
        <a href="?act=logout" class="btn">安全登出</a>
    </div>
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
