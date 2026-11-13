<?php
require APP_ROOT . '/core/bootstrap.php';
require_once __DIR__ . '/data.php';
$a3 = blog_load_data();
$a3['views'] = ($a3['views'] ?? 0) + 1;
blog_save_data($a3);
$site_views = $a3['views'];
$article_list = $a3['articles'];

if (isset($_GET['act']) && $_GET['act'] === 'like' && isset($_GET['id'])) {
    $art_id = trim($_GET['id'] ?? '');
    foreach ($article_list as $k => $v) {
        if ($v['art_id'] === $art_id) {
            $article_list[$k]['like_num'] = ($v['like_num'] ?? 0) + 1;
            break;
        }
    }
    $a3['articles'] = $article_list;
    blog_save_data($a3);
    header("Location: ?plugin=blog&page=frontend");
    exit;
}

$detail_art = [];
if (isset($_GET['act']) && $_GET['act'] === 'detail' && isset($_GET['id'])) {
    $detail_id = trim($_GET['id'] ?? '');
    foreach ($article_list as $art) {
        if ($art['art_id'] === $detail_id) {
            $detail_art = $art;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>博客主页 - <?=$SITE_NAME?></title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background 0.3s, color 0.3s, border-color 0.3s;
}
:root {
    --bg: #0a0a0a;
    --card: #121212;
    --text: #e0e0e0;
    --sub: #888;
    --line: #333;
    --accent: linear-gradient(90deg, #fff, #aaa);
    --border: #444;
    --btn-bg: #1a1a1a;
    --primary: #455A64;
    --like: #D32F2F;
}
.light {
    --bg: #ffffff;
    --card: #f5f5f5;
    --text: #1a1a1a;
    --sub: #666;
    --line: #ddd;
    --accent: linear-gradient(90deg, #000, #444);
    --border: #ccc;
    --btn-bg: #f0f0f0;
    --primary: #455A64;
    --like: #C62828;
}
body {
    background: var(--bg);
    color: var(--text);
    font-family: "Segoe UI", Roboto, monospace;
    min-height: 100vh;
    padding: 5rem 1.5rem;
    background-image: 
      linear-gradient(rgba(0,0,0,0.02), rgba(0,0,0,0.02)),
      url("data:image/svg+xml,%3Csvg width='30' height='30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h30v30H0z' fill='%23000' fill-opacity='0.03'/%3E%3Cpath d='M0 0l30 30M30 0L0 30' stroke='%23000' stroke-width='0.3' stroke-opacity='0.1'/%3E%3C/svg%3E");
}
.wrap {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}
.theme-toggle {
    position: absolute;
    top: -3rem;
    right: 0;
    padding: 0.6rem 1.2rem;
    background: var(--btn-bg);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    font-size: 0.9rem;
    border-radius: 6px;
}
.theme-toggle:hover {
    border-color: #777;
}
.blog-header {
    text-align: center;
    margin-bottom: 4rem;
}
h1 {
    font-size: 3.4rem;
    font-weight: 700;
    background: var(--accent);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.6rem;
}
.header-meta {
    display: flex;
    justify-content: center;
    gap: 2rem;
    color: var(--sub);
    font-size: 0.9rem;
    margin-top: 1rem;
}
.cyber-line {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 3rem 0;
}
.art-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
    transition: transform 0.3s, box-shadow 0.3s;
}
.art-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
body.light .art-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.art-title {
    font-size: 1.8rem;
    color: var(--text);
    margin-bottom: 1rem;
    text-decoration: none;
    display: block;
}
.art-meta {
    display: flex;
    justify-content: space-between;
    color: var(--sub);
    font-size: 0.85rem;
    margin-bottom: 1rem;
}
.art-excerpt {
    color: var(--sub);
    line-height: 1.7;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.art-btn {
    display: inline-block;
    padding: 0.6rem 1.5rem;
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
}
.art-btn:hover {
    background: #37474F;
}
.like-btn {
    background: transparent;
    border: 1px solid var(--like);
    color: var(--like);
    padding: 0.6rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    margin-left: 1rem;
}
.like-btn:hover {
    background: var(--like);
    color: #fff;
}
.detail-title {
    font-size: 2.2rem;
    text-align: center;
    margin-bottom: 2rem;
}
.detail-meta {
    display: flex;
    justify-content: center;
    gap: 2rem;
    color: var(--sub);
    margin-bottom: 2rem;
    text-align: center;
}
.detail-content {
    line-height: 2;
    font-size: 1.1rem;
    margin-bottom: 3rem;
}
.detail-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
}
.detail-content video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
}
.detail-content h1 {
    font-size: 1.8rem;
    margin: 2rem 0 1rem;
    background: var(--accent);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.detail-content h2 {
    font-size: 1.5rem;
    margin: 1.5rem 0 1rem;
    background: var(--accent);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.detail-content p {
    margin: 1rem 0;
}
.detail-content ul, .detail-content ol {
    padding-left: 2rem;
    margin: 1rem 0;
}
.detail-content li {
    margin: 0.5rem 0;
}
.detail-content a {
    color: #42A5F5;
    text-decoration: underline;
}
.back-btn {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: var(--btn-bg);
    border: 1px solid var(--border);
    color: var(--text);
    text-decoration: none;
    border-radius: 6px;
    margin-bottom: 2rem;
}
.back-btn:hover {
    border-color: var(--primary);
}
.no-art {
    text-align: center;
    color: var(--sub);
    font-size: 1.2rem;
    padding: 3rem 0;
}
.foot {
    margin-top: 7rem;
    font-size: 0.8rem;
    color: var(--sub);
    text-align: center;
}
@media (max-width: 480px) {
    body { padding: 4rem 1rem; }
    h1 { font-size: 2.2rem; }
    .header-meta { gap: 1rem; flex-wrap: wrap; }
    .art-card { padding: 1.5rem; }
    .art-title { font-size: 1.5rem; }
    .detail-title { font-size: 1.8rem; }
    .art-btn, .like-btn { display: block; width: 100%; margin: 0 0 1rem 0; text-align: center; }
}
</style>
<?php echo $pluginManager->triggerHook('head_assets'); ?>
<script src="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body>
<div class="wrap">
  <?php echo $pluginManager->triggerHook('body_start'); ?>
  <button class="theme-toggle" id="toggle">MODE ↻</button> 
  <div class="blog-header">
    <h1>博客主页</h1>
    <div class="header-meta">
      <span><i class="fa-solid fa-eye"></i> 总访问次数：<?=$site_views?></span>
      <span><i class="fa-solid fa-file-pen"></i> 文章总数：<?=count($article_list)?></span>
    </div>
  </div>
  <div class="cyber-line"></div>
  <?php if (!empty($detail_art)): ?>
  <a href="?plugin=blog&page=frontend" class="back-btn"><i class="fa-solid fa-arrow-left"></i> 返回文章列表</a>
  <h2 class="detail-title"><?=$detail_art['title']?></h2>
  <div class="detail-meta">
    <span>发布时间：<?=$detail_art['create_time']?></span>
    <span>点赞：<?=$detail_art['like_num'] ?? 0?></span>
  </div>
  <div class="cyber-line"></div>
  <div class="detail-content"><?=$detail_art['content']?></div>
  <?php echo $pluginManager->triggerHook('article_after', $detail_art); ?>
  <a href="?plugin=blog&page=frontend&act=like&id=<?=$detail_art['art_id']?>" class="like-btn"><i class="fa-solid fa-heart"></i> 点赞这篇文章</a>
  <?php else: ?>
  <?php if (empty($article_list)): ?>
  <div class="no-art">
    <i class="fa-solid fa-file-circle-exclamation" style="font-size:3rem; margin-bottom:1rem;"></i>
    <p>暂无博客文章，敬请期待...</p>
  </div>
  <?php else: ?>
  <?php foreach ($article_list as $art): ?>
  <div class="art-card">
    <a href="?plugin=blog&page=frontend&act=detail&id=<?=$art['art_id']?>" class="art-title"><?=$art['title']?></a>
    <div class="art-meta">
      <span><?=$art['create_time']?></span>
      <span><i class="fa-solid fa-heart" style="color:var(--like);"></i> <?=$art['like_num'] ?? 0?></span>
    </div>
    <div class="art-excerpt"><?=strip_tags($art['content'])?></div>
    <a href="?plugin=blog&page=frontend&act=detail&id=<?=$art['art_id']?>" class="art-btn">查看详情</a>
    <a href="?plugin=blog&page=frontend&act=like&id=<?=$art['art_id']?>" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> 点赞</a>
  </div>
  <?php echo $pluginManager->triggerHook('article_after', $art); ?>
  <?php endforeach; ?>
  <?php endif; ?>
  <?php endif; ?>
  <div class="cyber-line"></div>
  <div class="foot">© <?php echo date('Y'); ?> // <?=$SITE_NAME?></div>
</div>
<script>
const toggle = document.getElementById('toggle');
const html = document.documentElement;
if (localStorage.getItem('theme') === 'light') {
    html.classList.add('light');
    toggle.innerText = 'DARK ↻';
} else {
    html.classList.remove('light');
    toggle.innerText = 'LIGHT ↻';
}
toggle.addEventListener('click', () => {
    html.classList.toggle('light');
    if (html.classList.contains('light')) {
        localStorage.setItem('theme', 'light');
        toggle.innerText = 'DARK ↻';
    } else {
        localStorage.setItem('theme', 'dark');
        toggle.innerText = 'LIGHT ↻';
    }
});
</script>
<?php echo $pluginManager->triggerHook('body_end'); ?>
</body>
</html>