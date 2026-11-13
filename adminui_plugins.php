<?php
require 'core/bootstrap.php';

if (isset($_GET['plugin'])) {
    $pluginName = trim($_GET['plugin']);
    $page = trim($_GET['page'] ?? '');
    $pluginsDir = APP_ROOT . '/plugins/';
    if (file_exists($pluginsDir . $pluginName)) {
        $pageMap = [
            'frontend' => 'frontend.php',
            'admin_overview' => 'admin_overview.php',
            'admin_articles' => 'admin_articles.php',
            'admin' => 'admin.php'
        ];
        $pageFile = $pageMap[$page] ?? ($page . '.php');
        if (file_exists($pluginsDir . $pluginName . '/' . $pageFile)) {
            include $pluginsDir . $pluginName . '/' . $pageFile;
            exit;
        }
    }
    header("Location: adminui_plugins.php");
    exit;
}

requireAdmin();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['act'])) {
        switch ($_POST['act']) {
            case 'install':
                $file = $_FILES['plugin_zip'] ?? [];
                if (empty($file) || $file['error'] !== 0) {
                    $msg = '请选择插件压缩包';
                    $msgType = 'error';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'zip') {
                        $msg = '仅支持ZIP压缩包';
                        $msgType = 'error';
                    } else {
                        $tempPath = 'data/temp_' . time() . '.zip';
                        move_uploaded_file($file['tmp_name'], $tempPath);
                        $result = $pluginManager->installPlugin($tempPath);
                        unlink($tempPath);
                        $msg = $result['msg'];
                        $msgType = $result['code'] === 200 ? 'success' : 'error';
                    }
                }
                break;
            case 'enable':
                $pluginName = trim($_POST['plugin_name'] ?? '');
                $result = $pluginManager->enablePlugin($pluginName);
                $msg = $result['msg'];
                $msgType = $result['code'] === 200 ? 'success' : 'error';
                break;
            case 'disable':
                $pluginName = trim($_POST['plugin_name'] ?? '');
                $result = $pluginManager->disablePlugin($pluginName);
                $msg = $result['msg'];
                $msgType = $result['code'] === 200 ? 'success' : 'error';
                break;
            case 'uninstall':
                $pluginName = trim($_POST['plugin_name'] ?? '');
                $result = $pluginManager->uninstallPlugin($pluginName);
                $msg = $result['msg'];
                $msgType = $result['code'] === 200 ? 'success' : 'error';
                break;
            case 'update':
                $pluginName = trim($_POST['plugin_name'] ?? '');
                $file = $_FILES['update_zip'] ?? [];
                if (empty($file) || $file['error'] !== 0) {
                    $msg = '请选择新版本压缩包';
                    $msgType = 'error';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) !== 'zip') {
                        $msg = '仅支持ZIP压缩包';
                        $msgType = 'error';
                    } else {
                        $tempPath = 'data/temp_update_' . time() . '.zip';
                        move_uploaded_file($file['tmp_name'], $tempPath);
                        $result = $pluginManager->updatePlugin($pluginName, $tempPath);
                        unlink($tempPath);
                        $msg = $result['msg'];
                        $msgType = $result['code'] === 200 ? 'success' : 'error';
                    }
                }
                break;
        }
    }
}

$plugins = $pluginManager->getPlugins();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>插件管理中心-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:1000px; width:100%; margin:0 auto; }
.header { text-align:center; margin-bottom:40px; }
.header h1 { font-size:28px; color:#455A64; margin-bottom:10px; }
body.light .header h1 { color:#1F1F1F; }
.header p { color:#888; font-size:14px; }
body.light .header p { color:#666; }
.msg { padding:12px; border-radius:6px; margin-bottom:20px; text-align:center; }
.success { background:#E8F5E9; color:#2E7D32; }
.error { background:#FFEBEE; color:#C62828; }
.card { background:#121212; border-radius:12px; padding:30px; box-shadow:0 2px 8px rgba(0,0,0,0.5); margin-bottom:30px; transition:all 0.3s; }
body.light .card { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.card h2 { font-size:20px; color:#455A64; margin-bottom:20px; padding-left:10px; border-left:4px solid #455A64; }
body.light .card h2 { color:#1F1F1F; }
.upload-area { border:2px dashed #333; border-radius:8px; padding:40px; text-align:center; cursor:pointer; transition:all 0.3s; }
body.light .upload-area { border-color:#DCDCDC; }
.upload-area:hover { border-color:#455A64; background:#1a1a1a; }
body.light .upload-area:hover { background:#F5F5F5; }
.upload-area i { font-size:32px; margin-bottom:12px; display:block; }
.upload-area .text { font-size:16px; margin-bottom:8px; }
.upload-area .hint { font-size:12px; color:#888; }
body.light .upload-area .hint { color:#666; }
.upload-area input[type="file"] { display:none; }
.btn { padding:10px 24px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; font-size:14px; transition:all 0.3s; }
.btn:hover { background:#37474F; }
.btn-success { background:#2E7D32; }
.btn-success:hover { background:#1B5E20; }
.btn-danger { background:#D32F2F; }
.btn-danger:hover { background:#B71C1C; }
.btn-warning { background:#F57C00; }
.btn-warning:hover { background:#E65100; }
.btn-sm { padding:6px 16px; font-size:12px; }
.plugin-list { display:grid; gap:20px; }
.plugin-item { background:#1a1a1a; border-radius:8px; padding:20px; border:1px solid #333; transition:all 0.3s; }
body.light .plugin-item { background:#fff; border-color:#EAEAEA; }
.plugin-item:hover { border-color:#455A64; }
.plugin-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
.plugin-title { display:flex; align-items:center; gap:12px; }
.plugin-title .icon { width:40px; height:40px; border-radius:8px; background:#455A64; display:flex; align-items:center; justify-content:center; font-size:18px; }
.plugin-title .info h3 { font-size:16px; color:#fff; }
body.light .plugin-title .info h3 { color:#2D2D2D; }
.plugin-title .info span { font-size:12px; color:#888; }
body.light .plugin-title .info span { color:#666; }
.plugin-status { padding:4px 12px; border-radius:4px; font-size:12px; }
.status-enabled { background:#2E7D32; color:#fff; }
.status-disabled { background:#666; color:#fff; }
.plugin-desc { font-size:14px; color:#888; margin-bottom:15px; line-height:1.5; }
body.light .plugin-desc { color:#666; }
.plugin-meta { display:flex; gap:20px; margin-bottom:15px; font-size:12px; color:#888; }
body.light .plugin-meta { color:#666; }
.plugin-actions { display:flex; gap:10px; flex-wrap:wrap; }
.empty-state { text-align:center; padding:40px; color:#888; }
body.light .empty-state { color:#666; }
.empty-state i { font-size:48px; margin-bottom:16px; display:block; }
.empty-state p { font-size:16px; }
.back { display:block; text-align:center; margin-top:30px; color:#455A64; text-decoration:none; font-size:14px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
@media (max-width: 480px) {
    .container { padding:0; }
    .card { padding:20px; }
    .plugin-header { flex-direction:column; gap:10px; }
    .plugin-title { flex-direction:column; align-items:flex-start; }
    .btn { width:100%; margin-bottom:5px; }
    .plugin-actions { flex-direction:column; }
}
</style>

</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <div class="header">
        <h1>插件管理中心</h1>
        <p>上传、启用、禁用、更新和卸载插件</p>
    </div>

    <?php if ($msg): ?>
    <div class="msg <?=$msgType?>"><?=$msg?></div>
    <?php endif; ?>

    <div class="card">
        <h2>安装新插件</h2>
        <form method="post" enctype="multipart/form-data" id="installForm">
            <input type="hidden" name="act" value="install">
            <label class="upload-area" for="pluginZip">
                <i></i>
                <div class="text">点击选择插件 ZIP 压缩包</div>
                <div class="hint">支持 .zip 格式，必须包含 plugin.json 配置文件</div>
                <input type="file" id="pluginZip" name="plugin_zip" accept=".zip" required>
            </label>
            <div style="text-align:center; margin-top:20px;">
                <button type="submit" class="btn btn-success">安装插件</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2> 已安装插件</h2>
        <?php if (empty($plugins)): ?>
        <div class="empty-state">
            <i>🔌</i>
            <p>暂无已安装的插件</p>
            <p style="font-size:14px; margin-top:8px;">上传插件压缩包开始安装</p>
        </div>
        <?php else: ?>
        <div class="plugin-list">
            <?php foreach ($plugins as $plugin): ?>
            <div class="plugin-item">
                <div class="plugin-header">
                    <div class="plugin-title">
                        <div class="icon">⚙️</div>
                        <div class="info">
                            <h3><?=$plugin['title']?></h3>
                            <span><?=$plugin['name']?> v<?=$plugin['version']?></span>
                        </div>
                    </div>
                    <span class="plugin-status <?=$plugin['enabled'] ? 'status-enabled' : 'status-disabled'?>">
                        <?=$plugin['enabled'] ? '已启用' : '已禁用'?>
                    </span>
                </div>
                <div class="plugin-desc"><?=$plugin['description'] ?: '暂无描述'?></div>
                <div class="plugin-meta">
                    <?php if (!empty($plugin['hooks'])): ?>
                    <span>🎣 钩子: <?=implode(', ', $plugin['hooks'])?></span>
                    <?php endif; ?>
                </div>
                <div class="plugin-actions">
                    <?php if ($plugin['enabled']): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="act" value="disable">
                        <input type="hidden" name="plugin_name" value="<?=$plugin['name']?>">
                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('确定禁用该插件？')">禁用</button>
                    </form>
                    <?php else: ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="act" value="enable">
                        <input type="hidden" name="plugin_name" value="<?=$plugin['name']?>">
                        <button type="submit" class="btn btn-sm btn-success">启用</button>
                    </form>
                    <?php endif; ?>
                    <?php if (!empty($plugin['settings_url'])): ?>
                    <a href="<?=$plugin['settings_url']?>" class="btn btn-sm">设置</a>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" style="display:inline;">
                        <input type="hidden" name="act" value="update">
                        <input type="hidden" name="plugin_name" value="<?=$plugin['name']?>">
                        <label class="btn btn-sm" for="update_<?=$plugin['name']?>">🔄 更新</label>
                        <input type="file" id="update_<?=$plugin['name']?>" name="update_zip" accept=".zip" style="display:none" onchange="this.form.submit();">
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="act" value="uninstall">
                        <input type="hidden" name="plugin_name" value="<?=$plugin['name']?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定卸载该插件？此操作不可恢复！')">卸载</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <a href="adminui.php" class="back">返回后台总导航</a>
</div>

<script>
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

document.getElementById('installForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('pluginZip');
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        alert('请选择插件压缩包');
    }
});
</script>
</body>
</html>
