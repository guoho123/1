<?php
/**
 * xf_glass_theme 前台页面：项目详情（Markdown 渲染）
 * 访问方式：index.php?plugin=xf_glass_theme&page=project_detail&file=xxx.md
 */
require APP_ROOT . '/core/bootstrap.php';

$config = xf_theme_load_config();
$list = xf_theme_load_list('projects');

$file = trim($_GET['file'] ?? '');
$info = [];
foreach ($list as $item) {
    if (($item['file'] ?? '') === $file) { $info = $item; break; }
}
$md = $file !== '' ? xf_theme_read_md('projects', $file) : null;

$title = $info['title'] ?? ($file !== '' ? $file : '参数错误');
$date = $info['date'] ?? '';
$mdJson = json_encode($md, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$titleHtml = htmlspecialchars($title);
$metaHtml = $date !== '' ? '发布日期：' . htmlspecialchars($date) : '';
$error = '';
if ($file === '') {
    $error = '未指定项目文件，请从项目列表进入。';
} elseif ($md === null) {
    $error = '项目内容不存在或已被删除。';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($info['title'] ?? '项目详情')?> - <?=htmlspecialchars($SITE_NAME)?></title>
    <?php echo xf_theme_render_head_assets('a3.css'); ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .article-detail { max-width: 820px; margin: 0 auto 40px; padding: 0; grid-column: 1; }
        .article-back { display: inline-block; color: #ddd; text-decoration: none; font-size: 14px; margin-bottom: 18px; padding: 6px 14px; border: 1px solid rgba(255,255,255,0.18); border-radius: 8px; background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transition: background 0.2s, color 0.2s; }
        .article-back:hover { background: rgba(255,255,255,0.16); color: #fff; }
        .article-header { margin-bottom: 24px; }
        .article-title { font-size: 30px; font-weight: 700; color: #fff; line-height: 1.3; margin-bottom: 8px; text-shadow: 0 1px 3px rgba(0,0,0,0.4); }
        .article-meta { font-size: 13px; color: #bbb; }
        .article-body { color: #eee; font-size: 16px; line-height: 1.8; }
        .article-body h1,.article-body h2,.article-body h3,.article-body h4 { color: #fff; margin: 1.4em 0 0.6em; line-height: 1.3; }
        .article-body h1 { font-size: 26px; }
        .article-body h2 { font-size: 22px; border-bottom: 1px solid rgba(255,255,255,0.12); padding-bottom: 6px; }
        .article-body h3 { font-size: 18px; }
        .article-body p { margin: 0.8em 0; }
        .article-body a { color: #6db3ff; text-decoration: none; }
        .article-body a:hover { text-decoration: underline; }
        .article-body ul,.article-body ol { margin: 0.8em 0; padding-left: 1.6em; }
        .article-body li { margin: 0.3em 0; }
        .article-body blockquote { border-left: 3px solid rgba(255,255,255,0.35); padding: 6px 14px; margin: 1em 0; color: #ccc; background: rgba(255,255,255,0.04); border-radius: 0 8px 8px 0; }
        .article-body code { background: rgba(0,0,0,0.35); padding: 2px 6px; border-radius: 4px; font-size: 14px; color: #fceb92; }
        .article-body pre { background: rgba(0,0,0,0.45); padding: 14px 16px; border-radius: 10px; overflow-x: auto; border: 1px solid rgba(255,255,255,0.1); margin: 1em 0; }
        .article-body pre code { background: transparent; padding: 0; color: #eee; font-size: 14px; }
        .article-body img { max-width: 100%; border-radius: 10px; margin: 1em 0; }
        .article-body table { width: 100%; border-collapse: collapse; margin: 1em 0; font-size: 14px; }
        .article-body th,.article-body td { border: 1px solid rgba(255,255,255,0.15); padding: 8px 12px; text-align: left; }
        .article-body th { background: rgba(255,255,255,0.08); color: #fff; }
        .article-body hr { border: none; border-top: 1px solid rgba(255,255,255,0.15); margin: 1.6em 0; }
        .article-error { text-align: center; color: #ff9d9d; font-size: 16px; padding: 40px 0; }
        @media (max-width: 820px) { .article-detail { padding: 0 20px; } .article-title { font-size: 24px; } }
    </style>
</head>
<body>
<?php echo xf_theme_render_header('projects', $config); ?>

<main>
<article class="article-detail">
    <a href="<?=xf_theme_page_url('projects')?>" class="article-back">← 返回项目列表</a>
    <div class="article-header">
        <h1 class="article-title" id="articleTitle"><?=$titleHtml?></h1>
        <div class="article-meta" id="articleMeta"><?=$metaHtml?></div>
    </div>
    <div class="glass article-body" id="articleBody">
        <p>正在读取…</p>
    </div>
</article>

<?php echo xf_theme_render_side($config); ?>
</main>

<?php echo xf_theme_render_footer($config); ?>

<script>window.__XF_MD=<?=$mdJson?>;window.__XF_MD_ERROR=<?=json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)?>;</script>
<script src="<?=xf_theme_asset_url('xf-common.js')?>"></script>
<script src="<?=xf_theme_asset_url('xf-music-player.js')?>"></script>
<script>
// ===== 本页专属：Markdown 渲染 =====
(function(){
    var titleEl=document.getElementById('articleTitle');
    var bodyEl=document.getElementById('articleBody');
    if(window.__XF_MD_ERROR){
        if(titleEl && !titleEl.textContent.trim()) titleEl.innerText='读取失败';
        bodyEl.innerHTML='<div class="article-error">'+window.__XF_MD_ERROR+'</div>';
        return;
    }
    var md=window.__XF_MD;
    if(md==null){
        bodyEl.innerHTML='<div class="article-error">内容为空。</div>';
        return;
    }
    bodyEl.innerHTML=marked.parse(md);
})();
</script>
</body>
</html>
