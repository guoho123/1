<?php
/**
 * xf_glass_theme 后台管理页面
 * 访问方式：adminui_plugins.php?plugin=xf_glass_theme&page=admin
 *
 * 标签页：基本设置 / 社交按钮 / 其他网站 / 项目管理 / 文章管理
 */
require APP_ROOT . '/core/bootstrap.php';
requireAdmin();

// ===== 插件启用检查 =====
$pluginEnabled = false;
foreach ($pluginManager->getPlugins() as $p) {
    if ($p['name'] === 'xf_glass_theme' && $p['enabled']) { $pluginEnabled = true; break; }
}
if (!$pluginEnabled) {
    echo '<div style="text-align:center; padding:50px; background:#000; color:#fff; min-height:100vh;"><h2>小屋玻璃主题插件已禁用</h2><p>请在插件管理中心启用后再进行配置</p></div>';
    exit;
}

$config = xf_theme_load_config();
$projects = xf_theme_load_list('projects');
$articles = xf_theme_load_list('articles');

$validTabs = ['basic', 'socials', 'sites', 'friends', 'projects', 'articles'];
$tab = $_GET['tab'] ?? 'basic';
if (!in_array($tab, $validTabs, true)) $tab = 'basic';

$msg = '';
$msgType = 'success';
$currentUrl = 'adminui_plugins.php?plugin=xf_glass_theme&page=admin';

// ===== POST 保存处理 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])) {

    // 基本设置
    if ($_POST['act'] === 'save_config') {
        $newConfig = $config;
        $newConfig['site_name'] = trim($_POST['site_name'] ?? '') ?: '小屋';
        $newConfig['footer'] = trim($_POST['footer'] ?? '');
        $newConfig['nav_home'] = trim($_POST['nav_home'] ?? '') ?: '首页';
        $newConfig['nav_projects'] = trim($_POST['nav_projects'] ?? '') ?: '项目';
        $newConfig['nav_articles'] = trim($_POST['nav_articles'] ?? '') ?: '文章';
        $newConfig['profile'] = [
            'avatar' => $config['profile']['avatar'],
            'name' => trim($_POST['name'] ?? ''),
            'desc' => $config['profile']['desc']
        ];
        // 简介写全局 a2.json（与「个人主页管理」同一数据源，默认首页同步生效）
        xf_theme_save_global_bio($_POST['desc'] ?? '');
        $newConfig['stats'] = [
            'startDate' => trim($_POST['startDate'] ?? ''),
            'experienceStart' => trim($_POST['experienceStart'] ?? ''),
            'projectCount' => (int)($_POST['projectCount'] ?? 0)
        ];
        xf_theme_save_config($newConfig);
        $msg = '基本设置保存成功！';
        $tab = 'basic';
    }

    // 社交按钮
    elseif ($_POST['act'] === 'save_socials') {
        $socials = json_decode($_POST['socials'] ?? '[]', true);
        if (!is_array($socials)) $socials = [];
        $clean = [];
        foreach ($socials as $s) {
            if (!is_array($s)) continue;
            $clean[] = [
                'type' => ($s['type'] ?? 'link') === 'pic' ? 'pic' : 'link',
                'text' => (string)($s['text'] ?? ''),
                'pic' => (string)($s['pic'] ?? ''),
                'popup' => (string)($s['popup'] ?? ''),
                'href' => (string)($s['href'] ?? '')
            ];
        }
        $config['socials'] = $clean;
        xf_theme_save_config($config);
        $msg = '社交按钮保存成功！';
        $tab = 'socials';
    }

    // 我的其他网站
    elseif ($_POST['act'] === 'save_sites') {
        $sites = json_decode($_POST['sites'] ?? '[]', true);
        if (!is_array($sites)) $sites = [];
        $clean = [];
        foreach ($sites as $s) {
            if (!is_array($s)) continue;
            $clean[] = [
                'text' => (string)($s['text'] ?? ''),
                'href' => (string)($s['href'] ?? ''),
                'cover' => (string)($s['cover'] ?? '')
            ];
        }
        $config['sites'] = $clean;
        xf_theme_save_config($config);
        $msg = '其他网站保存成功！';
        $tab = 'sites';
    }

    // 友链
    elseif ($_POST['act'] === 'save_friends') {
        $friends = json_decode($_POST['friends'] ?? '[]', true);
        if (!is_array($friends)) $friends = [];
        $clean = [];
        foreach ($friends as $f) {
            if (!is_array($f)) continue;
            $clean[] = [
                'name' => (string)($f['name'] ?? ''),
                'url' => (string)($f['url'] ?? ''),
                'icon' => (string)($f['icon'] ?? ''),
                'desc' => (string)($f['desc'] ?? '')
            ];
        }
        $config['friends'] = $clean;
        xf_theme_save_config($config);
        $msg = '友链保存成功！';
        $tab = 'friends';
    }

    // 项目 / 文章列表（含 md 内容）
    elseif ($_POST['act'] === 'save_list') {
        $type = ($_POST['type'] ?? '') === 'articles' ? 'articles' : 'projects';
        $list = json_decode($_POST['list'] ?? '[]', true);
        if (!is_array($list)) $list = [];

        $oldFiles = [];
        foreach (($type === 'articles' ? $articles : $projects) as $item) {
            $f = $item['file'] ?? '';
            if (xf_theme_valid_md_name($f)) $oldFiles[] = $f;
        }

        $clean = [];
        $newFiles = [];
        foreach ($list as $item) {
            if (!is_array($item)) continue;
            $file = trim((string)($item['file'] ?? ''));
            if ($file === '' && trim((string)($item['title'] ?? '')) === '') continue;
            // 规范化文件名：仅允许字母数字下划线短横线，自动补 .md
            $file = preg_replace('/\.md$/i', '', $file);
            $file = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $file);
            if ($file === '') $file = 'post_' . (count($clean) + 1);
            $file .= '.md';
            if (in_array($file, $newFiles, true)) $file = str_replace('.md', '_' . time() . '.md', $file);
            $newFiles[] = $file;

            $md = str_replace("\r\n", "\n", (string)($item['md'] ?? ''));
            xf_theme_write_md($type, $file, $md);

            $clean[] = [
                'title' => trim((string)($item['title'] ?? '')) ?: '未命名',
                'file' => $file,
                'date' => trim((string)($item['date'] ?? '')),
                'summary' => (string)($item['summary'] ?? ''),
                'cover' => (string)($item['cover'] ?? '')
            ];
        }

        // 删除已移除条目的 md 文件
        foreach ($oldFiles as $f) {
            if (!in_array($f, $newFiles, true)) xf_theme_delete_md($type, $f);
        }

        if ($type === 'articles') {
            xf_theme_save_list('articles', $clean);
            $articles = $clean;
            $msg = '文章保存成功！';
            $tab = 'articles';
        } else {
            xf_theme_save_list('projects', $clean);
            $projects = $clean;
            $msg = '项目保存成功！';
            $tab = 'projects';
        }
    }

    if ($msg !== '') {
        header("Refresh:1; url=$currentUrl&tab=$tab");
        exit;
    }
}

// 重新读取（保存后刷新或正常加载）
$config = xf_theme_load_config();
$projects = xf_theme_load_list('projects');
$articles = xf_theme_load_list('articles');

// 给每条补 Markdown 正文（正文存于 data/md/{type}/，清单里不含）
foreach ($projects as &$it) { $it['md'] = xf_theme_read_md('projects', $it['file'] ?? '') ?? ''; }
unset($it);
foreach ($articles as &$it) { $it['md'] = xf_theme_read_md('articles', $it['file'] ?? '') ?? ''; }
unset($it);

$cfgJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$projectsJson = json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$articlesJson = json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>小屋玻璃主题设置-<?=$SITE_NAME?></title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:860px; width:100%; margin:0 auto; background:#121212; padding:40px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .container { background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); }
h1 { text-align:center; margin-bottom:30px; color:#455A64; }
/* 标签页 */
.tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px; }
.tab-btn { padding:10px 18px; border:1px solid #333; background:#1a1a1a; color:#ccc; cursor:pointer; border-radius:6px; font-size:14px; transition:all 0.3s; }
body.light .tab-btn { background:#FAFAFA; border:1px solid #DCDCDC; color:#555; }
.tab-btn.active { background:#455A64; color:#fff; border-color:#455A64; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
/* 表单 */
.form-item { margin-bottom:18px; }
label { display:block; margin-bottom:8px; font-size:14px; }
input[type="text"], input[type="date"], input[type="number"], textarea, select { width:100%; padding:12px; border:1px solid #333; border-radius:6px; background:#1a1a1a; color:#fff; font-size:14px; transition:all 0.3s; }
body.light input[type="text"], body.light input[type="date"], body.light input[type="number"], body.light textarea, body.light select { background:#FAFAFA; color:#2D2D2D; border:1px solid #DCDCDC; }
input:focus, textarea:focus, select:focus { outline:none; border-color:#455A64; }
textarea { min-height:90px; resize:vertical; }
textarea.md-editor { min-height:220px; font-family:Consolas, monospace; }
/* Markdown 编辑器：工具栏 + 左编辑右预览 + 字数 */
.md-toolbar { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; }
.md-btn { padding:6px 10px; border:1px solid #333; background:#1a1a1a; color:#ccc; border-radius:6px; cursor:pointer; font-size:12px; transition:all 0.2s; }
.md-btn:hover { border-color:#455A64; color:#fff; }
.md-btn:disabled { opacity:0.6; cursor:default; }
body.light .md-btn { background:#FAFAFA; border:1px solid #DCDCDC; color:#555; }
.md-split { display:flex; gap:12px; align-items:stretch; }
.md-split .md-editor { flex:1; min-height:280px; }
.md-preview { flex:1; min-height:280px; max-height:480px; overflow:auto; border:1px solid #333; border-radius:6px; background:#141414; padding:12px 14px; font-size:14px; line-height:1.7; overflow-wrap:break-word; }
body.light .md-preview { background:#FAFAFA; border:1px solid #DCDCDC; }
.md-preview h1,.md-preview h2,.md-preview h3 { margin:12px 0 8px; }
.md-preview p { margin:6px 0; }
.md-preview pre { background:#0d0d0d; border:1px solid #333; border-radius:6px; padding:10px; overflow:auto; }
body.light .md-preview pre { background:#F0F0F0; border-color:#DCDCDC; }
.md-preview code { font-family:Consolas, monospace; }
.md-preview blockquote { border-left:3px solid #455A64; padding-left:10px; color:#999; margin:6px 0; }
.md-preview table { border-collapse:collapse; }
.md-preview th,.md-preview td { border:1px solid #333; padding:6px 10px; }
.md-preview img { max-width:100%; }
.md-preview a { color:#82b8ff; }
.md-count { text-align:right; margin-top:4px; }
@media (max-width:640px){ .md-split { flex-direction:column; } }
.grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.hint { font-size:12px; color:#888; margin-top:5px; }
body.light .hint { color:#666; }
/* 动态行编辑器 */
.editor-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
.row-card { border:1px solid #333; border-radius:8px; padding:16px; margin-bottom:14px; background:#181818; transition:all 0.3s; }
body.light .row-card { background:#FAFAFA; border:1px solid #DCDCDC; }
.row-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.row-title { font-size:14px; font-weight:600; color:#82b8ff; }
.del-row { padding:6px 12px; border:1px solid #C62828; background:transparent; color:#ef9a9a; border-radius:6px; cursor:pointer; font-size:12px; }
.del-row:hover { background:#C62828; color:#fff; }
.btn { padding:10px 30px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; font-size:14px; }
.btn:hover { background:#37474F; }
.btn-sm { padding:8px 16px; font-size:13px; }
.btn-green { background:#2E7D32; }
.btn-green:hover { background:#1B5E20; }
.field { margin-bottom:10px; }
.field label { font-size:12px; color:#999; margin-bottom:4px; }
body.light .field label { color:#777; }
/* 提示 */
.msg { padding:12px; border-radius:6px; margin-bottom:20px; text-align:center; }
.success { background:#E8F5E9; color:#2E7D32; }
.error { background:#FFEBEE; color:#C62828; }
.back { display:block; text-align:center; margin-top:30px; color:#455A64; text-decoration:none; font-size:14px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
@media (max-width:640px){ .grid2,.grid3 { grid-template-columns:1fr; } }
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <h1>小屋玻璃主题设置</h1>

    <?php if ($msg): ?>
    <div class="msg <?php echo strpos($msg, '成功')!==false ? 'success' : 'error'; ?>"><?=$msg?></div>
    <?php endif; ?>

    <!-- 标签按钮 -->
    <div class="tabs">
        <button class="tab-btn" data-tab="basic">基本设置</button>
        <button class="tab-btn" data-tab="socials">社交按钮</button>
        <button class="tab-btn" data-tab="sites">其他网站</button>
        <button class="tab-btn" data-tab="friends">友链管理</button>
        <button class="tab-btn" data-tab="projects">项目管理</button>
        <button class="tab-btn" data-tab="articles">文章管理</button>
    </div>

    <!-- ============ 基本设置 ============ -->
    <div class="tab-pane" id="pane-basic">
        <form method="post">
            <input type="hidden" name="act" value="save_config">
            <div class="grid2">
                <div class="form-item">
                    <label>站点名称（顶部 Logo 文字）</label>
                    <input type="text" name="site_name" value="<?=htmlspecialchars($config['site_name'])?>">
                </div>
                <div class="form-item">
                    <label>昵称</label>
                    <input type="text" name="name" value="<?=htmlspecialchars($config['profile']['name'])?>">
                </div>
            </div>
            <div class="hint" style="margin-bottom:18px;">头像与个人简介为全局数据（a2.json），保存后默认首页与小屋主题同步生效。</div>
            <div class="grid2">
                <div class="form-item">
                    <label>头像（上传后立即保存到全局）</label>
                    <img id="avatarPreview" src="<?=htmlspecialchars($config['profile']['avatar'])?>" alt="头像" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #455A64;display:block;margin:6px 0;">
                    <div class="hint" id="avatarUrlText" style="word-break:break-all;"><?=htmlspecialchars($config['profile']['avatar'])?></div>
                    <button type="button" class="btn btn-sm" style="margin-top:6px;" onclick="uploadGlobalAvatar(this)">上传图片</button>
                </div>
                <div class="form-item">
                    <label>个人简介（每行一条）</label>
                    <textarea name="desc" style="min-height:120px;"><?=htmlspecialchars($config['profile']['desc'])?></textarea>
                </div>
            </div>
           
            
            <div class="grid3">
                <div class="form-item">
                    <label>入坑日期</label>
                    <input type="date" name="startDate" value="<?=htmlspecialchars($config['stats']['startDate'])?>">
                </div>
                <div class="form-item">
                    <label>经验开始日期</label>
                    <input type="date" name="experienceStart" value="<?=htmlspecialchars($config['stats']['experienceStart'])?>">
                </div>
                <div class="form-item">
                    <label>项目数量</label>
                    <input type="number" name="projectCount" value="<?=htmlspecialchars($config['stats']['projectCount'])?>" min="0">
                </div>
            </div>
            <div class="form-item">
                <label>页脚文字（{year} 会自动替换为当前年份）</label>
                <input type="text" name="footer" value="<?=htmlspecialchars($config['footer'])?>">
            </div>
            <button type="submit" class="btn">保存基本设置</button>
        </form>
    </div>

    <!-- ============ 社交按钮 ============ -->
    <div class="tab-pane" id="pane-socials">
        <form method="post" id="socialsForm">
            <input type="hidden" name="act" value="save_socials">
            <input type="hidden" name="socials" id="socialsData">
            <div class="editor-toolbar">
                <span class="hint">显示在头像下方的圆形按钮：pic 类型=点击弹出图片（如二维码），link 类型=直接跳转链接。图标支持 HTML（如 &lt;img src="..."&gt;）或 emoji。</span>
                <button type="button" class="btn btn-sm btn-green" onclick="addSocial()">+ 添加按钮</button>
            </div>
            <div id="socialsRows"></div>
            <button type="submit" class="btn">保存社交按钮</button>
        </form>
    </div>

    <!-- ============ 其他网站 ============ -->
    <div class="tab-pane" id="pane-sites">
        <form method="post" id="sitesForm">
            <input type="hidden" name="act" value="save_sites">
            <input type="hidden" name="sites" id="sitesData">
            <div class="editor-toolbar">
                <span class="hint">显示在「我的其他网站」卡片区，封面留空显示"无图"占位。</span>
                <button type="button" class="btn btn-sm btn-green" onclick="addSite()">+ 添加网站</button>
            </div>
            <div id="sitesRows"></div>
            <button type="submit" class="btn">保存其他网站</button>
        </form>
    </div>

    <!-- ============ 友链管理 ============ -->
    <div class="tab-pane" id="pane-friends">
        <form method="post" id="friendsForm">
            <input type="hidden" name="act" value="save_friends">
            <input type="hidden" name="friends" id="friendsData">
            <div class="editor-toolbar">
                <span class="hint">显示在「友链」页面，点击卡片跳转对方站点；图标留空显示 🔗 占位。</span>
                <button type="button" class="btn btn-sm btn-green" onclick="addFriend()">+ 添加友链</button>
            </div>
            <div id="friendsRows"></div>
            <button type="submit" class="btn">保存友链</button>
        </form>
    </div>

    <!-- ============ 项目管理 ============ -->
    <div class="tab-pane" id="pane-projects">
        <form method="post" id="projectsForm">
            <input type="hidden" name="act" value="save_list">
            <input type="hidden" name="type" value="projects">
            <input type="hidden" name="list" id="projectsData">
            <div class="editor-toolbar">
                <span class="hint">文件名仅限英文字母/数字/下划线/短横线（自动补 .md）；删除条目保存后其内容文件将一并删除。</span>
                <button type="button" class="btn btn-sm btn-green" onclick="addListItem('projects')">+ 添加项目</button>
            </div>
            <div id="projectsRows"></div>
            <button type="submit" class="btn">保存项目</button>
        </form>
    </div>

    <!-- ============ 文章管理 ============ -->
    <div class="tab-pane" id="pane-articles">
        <form method="post" id="articlesForm">
            <input type="hidden" name="act" value="save_list">
            <input type="hidden" name="type" value="articles">
            <input type="hidden" name="list" id="articlesData">
            <div class="editor-toolbar">
                <span class="hint">同项目管理；内容使用 Markdown 语法编写。</span>
                <button type="button" class="btn btn-sm btn-green" onclick="addListItem('articles')">+ 添加文章</button>
            </div>
            <div id="articlesRows"></div>
            <button type="submit" class="btn">保存文章</button>
        </form>
    </div>

    <a href="adminui.php" class="back">返回总导航</a>
</div>

<script>
window.__XF_ADMIN_CFG = <?=$cfgJson?>;
window.__XF_ADMIN_PROJECTS = <?=$projectsJson?>;
window.__XF_ADMIN_ARTICLES = <?=$articlesJson?>;
</script>
<script>
// ===== 通用工具 =====
function esc(s){
    return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
// ===== 图片上传（保存到插件 assets/img，回填 URL） =====
function uploadImage(btn){
    var wrap = btn.closest('.form-item, .field');
    var input = wrap ? wrap.querySelector('input[type="text"]') : null;
    if (!input) return;
    var inp = document.createElement('input');
    inp.type = 'file';
    inp.accept = 'image/*';
    inp.onchange = function(){
        if (!inp.files || !inp.files[0]) return;
        var old = btn.innerText;
        btn.innerText = '上传中…';
        btn.disabled = true;
        var fd = new FormData();
        fd.append('act', 'plugin_api');
        fd.append('route', 'xf_glass_theme_upload_img');
        fd.append('params', '{}');
        fd.append('file', inp.files[0]);
        fetch('API.php', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.code === 200 && res.data && res.data.url) {
                    input.value = res.data.url;
                    btn.innerText = '上传成功';
                    setTimeout(function(){ btn.innerText = old; }, 1200);
                } else {
                    alert(res.msg || '上传失败');
                    btn.innerText = old;
                }
            })
            .catch(function(){ alert('上传失败，请重试'); btn.innerText = old; })
            .finally(function(){ btn.disabled = false; });
    };
    inp.click();
}
// 为图片 URL 输入框生成「上传图片」按钮（图片保存到插件 assets/img）
function uploadBtnHtml(){
    return '<button type="button" class="btn btn-sm" style="margin-top:6px;" onclick="uploadImage(this)">上传图片</button>';
}
// 上传全局头像（保存到 {APP_ROOT}/1/jpg/avatar{N+1}.jpg 并更新 a2.json）
function uploadGlobalAvatar(btn){
    var inp = document.createElement('input');
    inp.type = 'file';
    inp.accept = 'image/*';
    inp.onchange = function(){
        if (!inp.files || !inp.files[0]) return;
        var old = btn.innerText;
        btn.innerText = '上传中…';
        btn.disabled = true;
        var fd = new FormData();
        fd.append('act', 'plugin_api');
        fd.append('route', 'xf_glass_theme_upload_avatar');
        fd.append('params', '{}');
        fd.append('file', inp.files[0]);
        fetch('API.php', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.code === 200 && res.data && res.data.url) {
                    var img = document.getElementById('avatarPreview');
                    if (img) img.src = res.data.url + '?t=' + Date.now();
                    var txt = document.getElementById('avatarUrlText');
                    if (txt) txt.textContent = res.data.url;
                    btn.innerText = '上传成功';
                    setTimeout(function(){ btn.innerText = old; }, 1200);
                } else {
                    alert(res.msg || '上传失败');
                    btn.innerText = old;
                }
            })
            .catch(function(){ alert('上传失败，请重试'); btn.innerText = old; })
            .finally(function(){ btn.disabled = false; });
    };
    inp.click();
}
// ===== 标签页 =====
var ACTIVE_TAB = new URLSearchParams(location.search).get('tab') || 'basic';
function activateTab(name){
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.tab===name); });
    document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.toggle('active', p.id==='pane-'+name); });
}
document.querySelectorAll('.tab-btn').forEach(function(b){
    b.addEventListener('click', function(){ activateTab(b.dataset.tab); });
});

// ===== 社交按钮编辑器 =====
function socialRowHtml(s, idx){
    var isPic = s.type==='pic';
    return '<div class="row-card" data-idx="'+idx+'">'
        + '<div class="row-head"><span class="row-title">按钮 #'+(idx+1)+'</span>'
        + '<button type="button" class="del-row" onclick="delRow(this,\'socials\')">删除</button></div>'
        + '<div class="grid2">'
        + '<div class="field"><label>类型</label><select onchange="toggleSocialType(this)">'
        + '<option value="link"'+(!isPic?' selected':'')+'>link - 跳转链接</option>'
        + '<option value="pic"'+(isPic?' selected':'')+'>pic - 弹出图片</option>'
        + '</select></div>'
        + '<div class="field"><label>图标（HTML 或 emoji）</label><input type="text" data-field="text" value="'+esc(s.text)+'"></div>'
        + '</div>'
        + '<div class="grid2 social-pic-fields" style="'+(isPic?'':'display:none')+'">'
        + '<div class="field"><label>弹窗图片 URL</label><input type="text" data-field="pic" value="'+esc(s.pic)+'">'+uploadBtnHtml()+'</div>'
        + '<div class="field"><label>弹窗文字（可留空）</label><input type="text" data-field="popup" value="'+esc(s.popup)+'"></div>'
        + '</div>'
        + '<div class="field social-link-field" style="'+(isPic?'display:none':'')+'">'
        + '<label>跳转链接 URL</label><input type="text" data-field="href" value="'+esc(s.href)+'"></div>'
        + '</div>';
}
function renderSocials(){
    var arr = window.__XF_ADMIN_CFG.socials || [];
    var box = document.getElementById('socialsRows');
    box.innerHTML = arr.map(function(s,i){ return socialRowHtml(s,i); }).join('') || '<div class="hint">暂无社交按钮，点击右上角添加。</div>';
}
function toggleSocialType(sel){
    var card = sel.closest('.row-card');
    var isPic = sel.value==='pic';
    card.querySelector('.social-pic-fields').style.display = isPic?'':'none';
    card.querySelector('.social-link-field').style.display = isPic?'none':'';
}
function addSocial(){
    var box = document.getElementById('socialsRows');
    if (box.querySelector('.hint')) box.innerHTML='';
    var tmp = document.createElement('div');
    tmp.innerHTML = socialRowHtml({type:'link',text:'🔗',pic:'',popup:'',href:'https://'}, box.querySelectorAll('.row-card').length);
    box.appendChild(tmp.firstChild);
}
function collectSocials(){
    var out=[];
    document.querySelectorAll('#socialsRows .row-card').forEach(function(card){
        out.push({
            type: card.querySelector('select').value,
            text: card.querySelector('[data-field="text"]').value,
            pic: card.querySelector('[data-field="pic"]') ? card.querySelector('[data-field="pic"]').value : '',
            popup: card.querySelector('[data-field="popup"]') ? card.querySelector('[data-field="popup"]').value : '',
            href: card.querySelector('[data-field="href"]') ? card.querySelector('[data-field="href"]').value : ''
        });
    });
    return out;
}

// ===== 其他网站编辑器 =====
function siteRowHtml(s, idx){
    return '<div class="row-card" data-idx="'+idx+'">'
        + '<div class="row-head"><span class="row-title">网站 #'+(idx+1)+'</span>'
        + '<button type="button" class="del-row" onclick="delRow(this,\'sites\')">删除</button></div>'
        + '<div class="field"><label>名称</label><input type="text" data-field="text" value="'+esc(s.text)+'"></div>'
        + '<div class="field"><label>链接 URL</label><input type="text" data-field="href" value="'+esc(s.href)+'"></div>'
        + '<div class="field"><label>封面图 URL（可留空）</label><input type="text" data-field="cover" value="'+esc(s.cover)+'">'+uploadBtnHtml()+'</div>'
        + '</div>';
}
function renderSites(){
    var arr = window.__XF_ADMIN_CFG.sites || [];
    var box = document.getElementById('sitesRows');
    box.innerHTML = arr.map(function(s,i){ return siteRowHtml(s,i); }).join('') || '<div class="hint">暂无网站卡片。</div>';
}
function addSite(){
    var box = document.getElementById('sitesRows');
    if (box.querySelector('.hint')) box.innerHTML='';
    var tmp = document.createElement('div');
    tmp.innerHTML = siteRowHtml({text:'',href:'https://',cover:''}, box.querySelectorAll('.row-card').length);
    box.appendChild(tmp.firstChild);
}
function collectSites(){
    var out=[];
    document.querySelectorAll('#sitesRows .row-card').forEach(function(card){
        out.push({
            text: card.querySelector('[data-field="text"]').value,
            href: card.querySelector('[data-field="href"]').value,
            cover: card.querySelector('[data-field="cover"]').value
        });
    });
    return out;
}

// ===== 友链编辑器 =====
function friendRowHtml(f, idx){
    return '<div class="row-card" data-idx="'+idx+'">'
        + '<div class="row-head"><span class="row-title">友链 #'+(idx+1)+'</span>'
        + '<button type="button" class="del-row" onclick="delRow(this,\'friends\')">删除</button></div>'
        + '<div class="grid2">'
        + '<div class="field"><label>站点名称</label><input type="text" data-field="name" value="'+esc(f.name)+'"></div>'
        + '<div class="field"><label>站点链接 URL</label><input type="text" data-field="url" value="'+esc(f.url)+'"></div>'
        + '</div>'
        + '<div class="grid2">'
        + '<div class="field"><label>图标 URL（可留空）</label><input type="text" data-field="icon" value="'+esc(f.icon)+'">'+uploadBtnHtml()+'</div>'
        + '<div class="field"><label>描述（可留空）</label><input type="text" data-field="desc" value="'+esc(f.desc)+'"></div>'
        + '</div>'
        + '</div>';
}
function renderFriends(){
    var arr = window.__XF_ADMIN_CFG.friends || [];
    var box = document.getElementById('friendsRows');
    box.innerHTML = arr.map(function(f,i){ return friendRowHtml(f,i); }).join('') || '<div class="hint">暂无友链，点击右上角添加。</div>';
}
function addFriend(){
    var box = document.getElementById('friendsRows');
    if (box.querySelector('.hint')) box.innerHTML='';
    var tmp = document.createElement('div');
    tmp.innerHTML = friendRowHtml({name:'',url:'https://',icon:'',desc:''}, box.querySelectorAll('.row-card').length);
    box.appendChild(tmp.firstChild);
}
function collectFriends(){
    var out=[];
    document.querySelectorAll('#friendsRows .row-card').forEach(function(card){
        out.push({
            name: card.querySelector('[data-field="name"]').value,
            url: card.querySelector('[data-field="url"]').value,
            icon: card.querySelector('[data-field="icon"]').value,
            desc: card.querySelector('[data-field="desc"]').value
        });
    });
    return out;
}

// ===== 项目 / 文章编辑器（列表 + md 内容） =====
function listItemRowHtml(item, idx, kind){
    return '<div class="row-card" data-kind="'+kind+'">'
        + '<div class="row-head"><span class="row-title">'+(kind==='projects'?'项目':'文章')+' #'+(idx+1)+'</span>'
        + '<div style="display:flex;gap:8px;">'
        + '<button type="button" class="btn btn-sm" onclick="toggleMd(this)">编辑内容</button>'
        + '<button type="button" class="del-row" onclick="delRow(this,\''+kind+'\')">删除</button>'
        + '</div></div>'
        + '<div class="grid2">'
        + '<div class="field"><label>标题</label><input type="text" data-field="title" value="'+esc(item.title)+'"></div>'
        + '<div class="field"><label>文件名（英文，自动补 .md）</label><input type="text" data-field="file" value="'+esc(item.file)+'"></div>'
        + '</div>'
        + '<div class="grid3">'
        + '<div class="field"><label>日期</label><input type="date" data-field="date" value="'+esc(item.date)+'"></div>'
        + '<div class="field"><label>摘要</label><input type="text" data-field="summary" value="'+esc(item.summary)+'"></div>'
        + '<div class="field"><label>封面图 URL</label><input type="text" data-field="cover" value="'+esc(item.cover)+'">'+uploadBtnHtml()+'</div>'
        + '</div>'
        + '<div class="field md-wrap" style="display:none;">'
        + '<label>Markdown 内容</label>'
        + '<div class="md-toolbar">'
        + '<button type="button" class="md-btn" data-md="bold">加粗</button>'
        + '<button type="button" class="md-btn" data-md="italic">斜体</button>'
        + '<button type="button" class="md-btn" data-md="h1">H1</button>'
        + '<button type="button" class="md-btn" data-md="h2">H2</button>'
        + '<button type="button" class="md-btn" data-md="h3">H3</button>'
        + '<button type="button" class="md-btn" data-md="link">链接</button>'
        + '<button type="button" class="md-btn" data-md="img">图片</button>'
        + '<button type="button" class="md-btn" data-md="quote">引用</button>'
        + '<button type="button" class="md-btn" data-md="ul">列表</button>'
        + '<button type="button" class="md-btn" data-md="ol">编号</button>'
        + '<button type="button" class="md-btn" data-md="code">代码</button>'
        + '<button type="button" class="md-btn" data-md="table">表格</button>'
        + '<button type="button" class="md-btn" data-md="hr">分割线</button>'
        + '<button type="button" class="md-btn" data-md="imgup">传图</button>'
        + '</div>'
        + '<div class="md-split">'
        + '<textarea class="md-editor" data-field="md" placeholder="在此输入 Markdown 内容...">'+esc(item.md||'')+'</textarea>'
        + '<div class="md-preview"></div>'
        + '</div>'
        + '<div class="hint md-count">0 字</div>'
        + '</div>'
        + '</div>';
}
function renderList(kind){
    var arr = kind==='projects' ? window.__XF_ADMIN_PROJECTS : window.__XF_ADMIN_ARTICLES;
    var box = document.getElementById(kind+'Rows');
    box.innerHTML = arr.map(function(item,i){ return listItemRowHtml(item,i,kind); }).join('') || '<div class="hint">暂无内容，点击右上角添加。</div>';
    box.querySelectorAll('.md-editor').forEach(mdRefresh);
}
function addListItem(kind){
    var box = document.getElementById(kind+'Rows');
    if (box.querySelector('.hint')) box.innerHTML='';
    var tmp = document.createElement('div');
    var item = {title:'',file:'',date:new Date().toISOString().slice(0,10),summary:'',cover:'',md:''};
    tmp.innerHTML = listItemRowHtml(item, box.querySelectorAll('.row-card').length, kind);
    var card = tmp.firstChild;
    card.querySelector('.md-wrap').style.display='';
    box.appendChild(card);
    mdRefresh(card.querySelector('.md-editor'));
}
function toggleMd(btn){
    var wrap = btn.closest('.row-card').querySelector('.md-wrap');
    var open = wrap.style.display!=='none';
    wrap.style.display = open?'none':'';
    btn.innerText = open?'编辑内容':'收起内容';
}

// ===== Markdown 编辑器（工具栏 / 实时预览 / 字数 / 传图） =====
function mdRefresh(ta){
    var wrap = ta.closest('.md-wrap');
    if (!wrap) return;
    var prev = wrap.querySelector('.md-preview');
    var cnt = wrap.querySelector('.md-count');
    var val = ta.value || '';
    if (prev) {
        if (window.marked) { try { prev.innerHTML = marked.parse(val); } catch(e) { prev.textContent = val; } }
        else prev.textContent = val;
    }
    if (cnt) cnt.textContent = val.length + ' 字';
}
function mdInsert(ta, before, after, placeholder){
    var s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.slice(s, e) || placeholder || '';
    ta.value = ta.value.slice(0, s) + before + sel + after + ta.value.slice(e);
    ta.focus();
    ta.selectionStart = s + before.length;
    ta.selectionEnd = s + before.length + sel.length;
    ta.dispatchEvent(new Event('input', { bubbles: true }));
}
function mdLinePrefix(ta, prefix){
    var s = ta.selectionStart, e = ta.selectionEnd;
    var val = ta.value;
    var ls = val.lastIndexOf('\n', s - 1) + 1;
    var le = val.indexOf('\n', e); if (le < 0) le = val.length;
    var lines = val.slice(ls, le).split('\n').map(function(l, i){
        return prefix.replace('{n}', i + 1) + l;
    }).join('\n');
    ta.value = val.slice(0, ls) + lines + val.slice(le);
    ta.focus();
    ta.selectionStart = ls;
    ta.selectionEnd = ls + lines.length;
    ta.dispatchEvent(new Event('input', { bubbles: true }));
}
function mdUploadImg(btn, ta){
    var inp = document.createElement('input');
    inp.type = 'file';
    inp.accept = 'image/*';
    inp.onchange = function(){
        if (!inp.files || !inp.files[0]) return;
        var old = btn.innerText;
        btn.innerText = '上传中…';
        btn.disabled = true;
        var fd = new FormData();
        fd.append('act', 'plugin_api');
        fd.append('route', 'xf_glass_theme_upload_img');
        fd.append('params', '{}');
        fd.append('file', inp.files[0]);
        fetch('API.php', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.code === 200 && res.data && res.data.url) {
                    mdInsert(ta, '![img](', ')', res.data.url);
                    btn.innerText = old;
                } else {
                    alert(res.msg || '上传失败');
                    btn.innerText = old;
                }
            })
            .catch(function(){ alert('上传失败，请重试'); btn.innerText = old; })
            .finally(function(){ btn.disabled = false; });
    };
    inp.click();
}
// 工具栏事件（行是动态生成的，用事件委托）
document.addEventListener('click', function(e){
    var btn = e.target.closest('.md-btn');
    if (!btn) return;
    var wrap = btn.closest('.md-wrap');
    if (!wrap) return;
    var ta = wrap.querySelector('.md-editor');
    if (!ta) return;
    switch (btn.dataset.md) {
        case 'bold':   mdInsert(ta, '**', '**', '加粗文字'); break;
        case 'italic': mdInsert(ta, '*', '*', '斜体文字'); break;
        case 'h1':     mdLinePrefix(ta, '# '); break;
        case 'h2':     mdLinePrefix(ta, '## '); break;
        case 'h3':     mdLinePrefix(ta, '### '); break;
        case 'link':   mdInsert(ta, '[', '](https://)', '链接文字'); break;
        case 'img':    mdInsert(ta, '![', '](https://)', '图片描述'); break;
        case 'quote':  mdLinePrefix(ta, '> '); break;
        case 'ul':     mdLinePrefix(ta, '- '); break;
        case 'ol':     mdLinePrefix(ta, '{n}. '); break;
        case 'code':   mdInsert(ta, '\n```\n', '\n```\n', '代码'); break;
        case 'table':  mdInsert(ta, '\n| 表头1 | 表头2 |\n| --- | --- |\n| 内容 | 内容 |\n', '', ''); break;
        case 'hr':     mdInsert(ta, '\n---\n', '', ''); break;
        case 'imgup':  mdUploadImg(btn, ta); break;
    }
});
document.addEventListener('input', function(e){
    if (e.target && e.target.classList && e.target.classList.contains('md-editor')) mdRefresh(e.target);
});
function collectList(kind){
    var out=[];
    document.querySelectorAll('#'+kind+'Rows .row-card').forEach(function(card){
        out.push({
            title: card.querySelector('[data-field="title"]').value,
            file: card.querySelector('[data-field="file"]').value,
            date: card.querySelector('[data-field="date"]').value,
            summary: card.querySelector('[data-field="summary"]').value,
            cover: card.querySelector('[data-field="cover"]').value,
            md: card.querySelector('[data-field="md"]').value
        });
    });
    return out;
}

// ===== 通用删除 =====
function delRow(btn, kind){
    btn.closest('.row-card').remove();
    // 编号刷新
    document.querySelectorAll('#'+kind+'Rows .row-card .row-title').forEach(function(t,i){
        var base = kind==='socials'?'按钮':(kind==='sites'?'网站':(kind==='friends'?'友链':(kind==='projects'?'项目':'文章')));
        t.innerText = base+' #'+(i+1);
    });
}

// ===== 表单提交序列化 =====
document.getElementById('socialsForm').addEventListener('submit', function(){
    document.getElementById('socialsData').value = JSON.stringify(collectSocials());
});
document.getElementById('sitesForm').addEventListener('submit', function(){
    document.getElementById('sitesData').value = JSON.stringify(collectSites());
});
document.getElementById('friendsForm').addEventListener('submit', function(){
    document.getElementById('friendsData').value = JSON.stringify(collectFriends());
});
document.getElementById('projectsForm').addEventListener('submit', function(){
    document.getElementById('projectsData').value = JSON.stringify(collectList('projects'));
});
document.getElementById('articlesForm').addEventListener('submit', function(){
    document.getElementById('articlesData').value = JSON.stringify(collectList('articles'));
});

// ===== 初始化 =====
(function(){
    // 兼容非法 tab 值
    if (['basic','socials','sites','friends','projects','articles'].indexOf(ACTIVE_TAB)<0) ACTIVE_TAB='basic';
    activateTab(ACTIVE_TAB);
    renderSocials();
    renderSites();
    renderFriends();
    renderList('projects');
    renderList('articles');
})();
</script>

<script>
// ===== 标准暗色/亮色主题切换 =====
const themeToggle = document.getElementById('toggle');
if (localStorage.getItem('theme') === 'light') {
    document.body.classList.add('light');
    themeToggle.innerText = '切换暗色';
}
themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('light');
    if (document.body.classList.contains('light')) {
        localStorage.setItem('theme', 'light');
        themeToggle.innerText = '切换暗色';
    } else {
        localStorage.setItem('theme', 'dark');
        themeToggle.innerText = '切换亮色';
    }
});
</script>
</body>
</html>
