<?php
require APP_ROOT . '/core/bootstrap.php';
require_once __DIR__ . '/data.php';
requireAdmin();
$a3 = blog_load_data();
$site_views = $a3['views'];
$article_list = $a3['articles'];
$article_total = count($article_list);
$like_total = 0;
foreach ($article_list as $art) {
    $like_total += $art['like_num'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>博客后台总览-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; display:flex; justify-content:center; align-items:center; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:800px; width:100%; background:#121212; border-radius:12px; padding:40px; box-shadow:0 2px 8px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .container { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
h1 { font-size:28px; color:#455A64; margin-bottom:40px; text-align:center; transition:all 0.3s; }
body.light h1 { color:#1F1F1F; }
.data-box { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:40px; }
.data-card { padding:30px 20px; border:1px solid #333; border-radius:10px; text-align:center; background:#1a1a1a; transition:all 0.3s; }
body.light .data-card { border:1px solid #EAEAEA; background:#fff; }
.data-card .num { font-size:36px; font-weight:700; color:#455A64; margin:10px 0; }
body.light .data-card .num { color:#2E7D32; }
.data-card .name { font-size:16px; color:#888; }
body.light .data-card .name { color:#666; }
.btn-box { text-align:center; }
.btn { padding:12px 30px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; text-decoration:none; font-size:16px; transition:all 0.3s; }
.btn:hover { background:#37474F; }
.back { display:block; text-align:center; margin-top:20px; color:#455A64; text-decoration:none; font-size:14px; margin-top:30px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
@media (max-width: 480px) {
    .data-box { grid-template-columns:1fr; }
    .container { padding:20px; }
}
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <h1>博客后台数据总览</h1>
    <div class="data-box">
        <div class="data-card">
            <div class="num"><?=$article_total?></div>
            <div class="name">文章总数</div>
        </div>
        <div class="data-card">
            <div class="num"><?=$site_views?></div>
            <div class="name">网站总访问次数</div>
        </div>
        <div class="data-card">
            <div class="num"><?=$like_total?></div>
            <div class="name">文章点赞总量</div>
        </div>
    </div>
    <div class="btn-box">
        <a href="adminui_plugins.php?plugin=blog&page=admin_articles" class="btn">进入文章列表管理</a>
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
</script>
</body>
</html>