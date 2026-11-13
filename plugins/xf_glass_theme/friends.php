<?php
/**
 * xf_glass_theme 前台页面：友链
 * 访问方式：index.php?plugin=xf_glass_theme&page=friends
 */
require APP_ROOT . '/core/bootstrap.php';

$config = xf_theme_load_config();
$friends = $config['friends'] ?? [];
$friendsJson = json_encode($friends, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($config['nav_friends'] ?? '友链')?> - <?=htmlspecialchars($SITE_NAME)?></title>
    <?php echo xf_theme_render_head_assets('a3.css'); ?>
    <style>
        /* 友链卡片（插件前缀类名，避免冲突） */
        .xfgf-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .xfgf-card { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-radius: 14px; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; }
        .xfgf-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.35); }
        .xfgf-icon { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: rgba(255,255,255,0.1); }
        .xfgf-ph { width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 20px; background: rgba(255,255,255,0.1); }
        .xfgf-info { min-width: 0; }
        .xfgf-name { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .xfgf-desc { font-size: 12px; color: #bbb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .xfgf-empty { text-align: center; color: #bbb; padding: 40px 0; }
    </style>
</head>
<body>
<?php echo xf_theme_render_header('friends', $config); ?>

<main>
    <div class="left-col" style="grid-column: 1;">
        <div class="glass" style="padding:22px; margin-bottom:18px;">
            <div class="sites-title" style="margin-bottom:6px;"><?=htmlspecialchars($config['nav_friends'] ?? '友链')?></div>
            <div style="font-size:12px; color:#bbb;">点击卡片即可访问对方站点</div>
        </div>
        <div class="xfgf-list" id="friendList"></div>
    </div>

    <?php echo xf_theme_render_side($config); ?>
</main>

<?php echo xf_theme_render_footer($config); ?>

<script>window.__XF_FRIENDS=<?=$friendsJson?>;</script>
<script src="<?=xf_theme_asset_url('xf-common.js')?>"></script>
<script src="<?=xf_theme_asset_url('xf-music-player.js')?>"></script>

<script>
// ===== 本页专属：友链渲染 =====
(function(){
    var box = document.getElementById('friendList');
    var list = window.__XF_FRIENDS || [];
    if (!list.length) {
        box.innerHTML = '<div class="glass xfgf-empty">暂无友链，可在后台「小屋玻璃主题 → 友链管理」中添加。</div>';
        return;
    }
    box.innerHTML = list.map(function(f){
        var icon = f.icon
            ? '<img class="xfgf-icon" src="' + f.icon + '" alt="' + (f.name||'') + '" onerror="this.style.display=\'none\'">'
            : '<div class="xfgf-ph">🔗</div>';
        return '<a class="glass xfgf-card" href="' + (f.url||'#') + '" target="_blank" rel="noopener" title="' + (f.desc||'') + '">'
            + icon
            + '<div class="xfgf-info"><div class="xfgf-name">' + (f.name||'未命名') + '</div>'
            + '<div class="xfgf-desc">' + (f.desc||'') + '</div></div>'
            + '</a>';
    }).join('');
})();
</script>
</body>
</html>
