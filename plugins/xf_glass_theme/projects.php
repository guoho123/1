<?php
/**
 * xf_glass_theme 前台页面：项目列表
 * 访问方式：index.php?plugin=xf_glass_theme&page=projects
 */
require APP_ROOT . '/core/bootstrap.php';

$config = xf_theme_load_config();
$list = xf_theme_load_list('projects');
$listJson = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($config['nav_projects'])?> - <?=htmlspecialchars($SITE_NAME)?></title>
    <?php echo xf_theme_render_head_assets('a3.css'); ?>
</head>
<body>
<?php echo xf_theme_render_header('projects', $config); ?>

<main>
    <div class="left-col" id="articleList">
        <!-- 项目卡片由 JS 渲染 -->
    </div>

    <?php echo xf_theme_render_side($config); ?>
</main>

<?php echo xf_theme_render_footer($config); ?>

<script>window.__XF_LIST=<?=$listJson?>;</script>
<script src="<?=xf_theme_asset_url('xf-common.js')?>"></script>
<script src="<?=xf_theme_asset_url('xf-music-player.js')?>"></script>

<script>
// ===== 本页专属：项目列表 =====
function renderArticleList(list){
    var box=document.getElementById('articleList');
    if(!box) return;
    box.innerHTML='';
    if(!list.length){
        box.innerHTML='<div class="glass">暂无项目，可在后台「小屋玻璃主题」中添加。</div>';
        return;
    }
    list.forEach(function(a){
        var cover=a.cover || '<?=xf_theme_asset_url('img/cover_default.jpg')?>';
        var title=a.title || '未命名项目';
        var summary=a.summary || '';
        var date=a.date || '';
        var file=a.file || '';
        var card=document.createElement('div');
        card.className='glass time-card xf_timee xf_glass';
        card.innerHTML='<div class="card-img-skeleton" data-src="'+cover+'" data-text="'+title+'"><div class="skeleton-shimmer"></div></div><a class="hrefa" href="<?=xf_theme_page_url('project_detail')?>&file='+encodeURIComponent(file)+'">'+title+'</a>'+(date?'<div class="article-date">'+date+'</div>':'')+(summary?'<div class="article-summary">'+summary+'</div>':'');
        box.appendChild(card);
    });
    requestAnimationFrame(loadCardImages);
}
function loadCardImages(){
    var imgs=document.querySelectorAll('.card-img-skeleton');
    if(!imgs.length) return;
    if('IntersectionObserver' in window){
        var io=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    loadSingleImage(entry.target);
                    io.unobserve(entry.target);
                }
            });
        },{rootMargin:'100px'});
        imgs.forEach(function(img){ io.observe(img); });
    } else {
        imgs.forEach(function(img){ loadSingleImage(img); });
    }
}
function loadSingleImage(el){
    var src=el.dataset.src;
    var text=el.dataset.text || '';
    if(!src) return;
    var img=new Image();
    img.alt=text;
    img.className='xf-PicBlackbox xf-showImg';
    img.width=200;
    img.onload=function(){ el.replaceWith(img); };
    img.onerror=function(){ el.innerHTML='<div style="color:#aaa;font-size:12px;text-align:center;padding:20px 0">图片加载失败</div>'; };
    img.src=src;
}
renderArticleList(window.__XF_LIST || []);
</script>
</body>
</html>
