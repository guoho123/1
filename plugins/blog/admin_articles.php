<?php
require APP_ROOT . '/core/bootstrap.php';
require_once __DIR__ . '/data.php';
requireAdmin();
$a3 = blog_load_data();
$article_list = $a3['articles'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save_art') {
    $art_id = trim($_POST['art_id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (empty($title) || empty($content)) {
        $msg = '标题和内容不能为空！';
    } else {
        $article_data = [
            'art_id' => $art_id ?: uniqid('art_', true),
            'title' => $title,
            'content' => $content,
            'create_time' => $art_id ? ($_POST['old_time'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s'),
            'like_num' => $art_id ? ($_POST['old_like'] ?? 0) : 0
        ];
        if ($art_id) {
            foreach ($article_list as $k => $v) {
                if ($v['art_id'] === $art_id) {
                    $article_list[$k] = $article_data;
                    break;
                }
            }
            $msg = '文章编辑成功！';
        } else {
            array_unshift($article_list, $article_data);
            $msg = '文章新建成功！';
        }
        $a3['articles'] = $article_list;
        blog_save_data($a3);
        header("Refresh:1;url=adminui_plugins.php?plugin=blog&page=admin_articles");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'del_art') {
    $art_id = trim($_POST['art_id'] ?? '');
    $new_article_list = [];
    foreach ($article_list as $art) {
        if ($art['art_id'] !== $art_id) {
            $new_article_list[] = $art;
        }
    }
    $a3['articles'] = $new_article_list;
    blog_save_data($a3);
    $msg = '文章删除成功！';
    header("Refresh:1;url=adminui_plugins.php?plugin=blog&page=admin_articles");
    exit;
}

$edit_art = [];
if (isset($_GET['act']) && $_GET['act'] === 'edit' && isset($_GET['id'])) {
    $edit_id = trim($_GET['id'] ?? '');
    foreach ($article_list as $art) {
        if ($art['art_id'] === $edit_id) {
            $edit_art = $art;
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
<title>文章列表管理-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:1200px; width:100%; margin:0 auto; background:#121212; border-radius:12px; padding:40px; box-shadow:0 2px 8px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .container { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
h1 { font-size:28px; color:#455A64; margin-bottom:20px; text-align:center; }
h2 { font-size:20px; color:#455A64; margin:30px 0 20px; padding-left:10px; border-left:4px solid #455A64; }
body.light h1, body.light h2 { color:#1F1F1F; }
.msg { padding:12px; border-radius:6px; margin-bottom:20px; text-align:center; }
.success { background:#E8F5E9; color:#2E7D32; }
.error { background:#FFEBEE; color:#C62828; }
.form-item { margin-bottom:20px; }
label { display:block; margin-bottom:8px; font-size:14px; }
input, textarea { width:100%; padding:12px; border:1px solid #333; border-radius:6px; background:#1a1a1a; color:#fff; font-size:14px; transition:all 0.3s; }
body.light input, body.light textarea { background:#FAFAFA; color:#2D2D2D; border:1px solid #DCDCDC; }
textarea { min-height:200px; resize:vertical; }
.btn { padding:10px 20px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; text-decoration:none; font-size:14px; }
.btn:hover { background:#37474F; }
.del-btn { background:#D32F2F; margin-left:10px; }
.del-btn:hover { background:#B71C1C; }
.add-btn { background:#2E7D32; margin-bottom:20px; }
.add-btn:hover { background:#1B5E20; }
.table { width:100%; border-collapse:collapse; margin:20px 0; }
.table th, .table td { padding:12px; text-align:left; border-bottom:1px solid #333; }
body.light .table th, body.light .table td { border-bottom:1px solid #EAEAEA; }
.table th { background:#1a1a1a; color:#fff; }
body.light .table th { background:#F5F5F5; color:#2D2D2D; }
.back { display:block; text-align:center; margin-top:30px; color:#455A64; text-decoration:none; font-size:14px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; transition:all 0.3s; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
.editor-wrapper { border:1px solid #333; border-radius:6px; overflow:hidden; }
body.light .editor-wrapper { border:1px solid #DCDCDC; }
.editor-toolbar { display:flex; flex-wrap:wrap; gap:4px; padding:8px; background:#1a1a1a; border-bottom:1px solid #333; align-items:center; }
body.light .editor-toolbar { background:#F5F5F5; border-bottom:1px solid #DCDCDC; }
.editor-btn { padding:6px 10px; border:1px solid #333; background:#2a2a2a; color:#fff; cursor:pointer; border-radius:4px; font-size:12px; display:flex; align-items:center; gap:4px; transition:all 0.2s; }
body.light .editor-btn { background:#fff; color:#2D2D2D; border:1px solid #DCDCDC; }
.editor-btn:hover { background:#3a3a3a; }
body.light .editor-btn:hover { background:#E8E8E8; }
.editor-btn.active { background:#455A64; border-color:#455A64; }
body.light .editor-btn.active { background:#455A64; color:#fff; border-color:#455A64; }
.editor-btn i { font-style:normal; font-size:14px; }
.editor-divider { width:1px; height:20px; background:#333; margin:0 4px; }
body.light .editor-divider { background:#DCDCDC; }
.editor-content { min-height:300px; padding:16px; background:#121212; color:#fff; font-size:14px; outline:none; resize:vertical; }
body.light .editor-content { background:#fff; color:#2D2D2D; }
.editor-content:focus { outline:none; }
.editor-content h1 { font-size:24px; color:#fff; margin:16px 0; padding:0; border:none; }
.editor-content h2 { font-size:20px; color:#fff; margin:14px 0; padding:0; border:none; }
body.light .editor-content h1, body.light .editor-content h2 { color:#1F1F1F; }
.editor-content p { margin:8px 0; }
.editor-content ul, .editor-content ol { padding-left:24px; margin:8px 0; }
.editor-content li { margin:4px 0; }
.editor-content a { color:#42A5F5; text-decoration:underline; }
.editor-content img { max-width:100%; height:auto; border-radius:4px; }
.editor-content video { max-width:100%; height:auto; border-radius:4px; }
.editor-content:empty:before { content:attr(placeholder); color:#888; pointer-events:none; display:block; }
.modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
.modal-overlay.show { display:flex; }
.modal { background:#1a1a1a; border-radius:8px; padding:24px; width:90%; max-width:400px; box-shadow:0 4px 20px rgba(0,0,0,0.5); }
body.light .modal { background:#fff; }
.modal h3 { margin-bottom:16px; color:#fff; }
body.light .modal h3 { color:#1F1F1F; }
.modal-input { margin-bottom:12px; }
.modal-input label { display:block; margin-bottom:4px; }
.modal-input input { width:100%; padding:8px; border:1px solid #333; border-radius:4px; background:#121212; color:#fff; }
body.light .modal-input input { background:#FAFAFA; color:#2D2D2D; border-color:#DCDCDC; }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
.modal-btn { padding:8px 16px; border:none; border-radius:4px; cursor:pointer; font-size:14px; }
.modal-btn-primary { background:#455A64; color:#fff; }
.modal-btn-primary:hover { background:#37474F; }
.modal-btn-cancel { background:#333; color:#fff; }
body.light .modal-btn-cancel { background:#E0E0E0; color:#2D2D2D; }
.modal-btn-cancel:hover { background:#444; }
body.light .modal-btn-cancel:hover { background:#D0D0D0; }
.upload-area { border:2px dashed #333; border-radius:4px; padding:20px; text-align:center; cursor:pointer; margin-bottom:12px; }
body.light .upload-area { border-color:#DCDCDC; }
.upload-area:hover { border-color:#455A64; }
.upload-area i { font-size:24px; margin-bottom:8px; }
.upload-text { font-size:12px; color:#888; }
.preview-img { max-width:100%; max-height:200px; margin-top:12px; border-radius:4px; }
@media (max-width: 480px) {
    .container { padding:20px; }
    .table th, .table td { padding:8px; font-size:14px; }
    .btn { padding:8px 12px; display:block; margin:5px 0; width:100%; }
    .del-btn { margin-left:0; }
    .editor-btn { padding:4px 8px; font-size:11px; }
}
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <h1>博客文章列表管理</h1>
    <?php if ($msg): ?>
    <div class="msg <?php echo strpos($msg, '成功')!==false ? 'success' : 'error'; ?>"><?=$msg?></div>
    <?php endif; ?>
    <h2><?=!empty($edit_art) ? '编辑文章' : '新建文章'?></h2>
    <form method="post" id="editorForm">
        <input type="hidden" name="act" value="save_art">
        <input type="hidden" name="art_id" value="<?=$edit_art['art_id'] ?? ''?>">
        <input type="hidden" name="old_time" value="<?=$edit_art['create_time'] ?? ''?>">
        <input type="hidden" name="old_like" value="<?=$edit_art['like_num'] ?? 0?>">
        <div class="form-item">
            <label>文章标题 <span style="color:#D32F2F;">*</span></label>
            <input type="text" name="title" value="<?=$edit_art['title'] ?? ''?>" required placeholder="请输入文章标题">
        </div>
        <div class="form-item">
            <label>文章内容 <span style="color:#D32F2F;">*</span></label>
            <div class="editor-wrapper">
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" onclick="formatDoc('bold')" title="粗体"><i>B</i></button>
                    <button type="button" class="editor-btn" onclick="formatDoc('italic')" title="斜体"><i>I</i></button>
                    <button type="button" class="editor-btn" onclick="formatDoc('underline')" title="下划线"><i>U</i></button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" onclick="formatBlock('h1')" title="标题1">H1</button>
                    <button type="button" class="editor-btn" onclick="formatBlock('h2')" title="标题2">H2</button>
                    <button type="button" class="editor-btn" onclick="formatBlock('p')" title="段落">P</button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" onclick="formatDoc('insertUnorderedList')" title="无序列表">• 列表</button>
                    <button type="button" class="editor-btn" onclick="formatDoc('insertOrderedList')" title="有序列表">1. 列表</button>
                    <div class="editor-divider"></div>
                    <button type="button" class="editor-btn" onclick="showLinkModal()" title="插入链接">🔗 链接</button>
                    <button type="button" class="editor-btn" onclick="showImageModal()" title="插入图片">🖼️ 图片</button>
                    <button type="button" class="editor-btn" onclick="showVideoModal()" title="插入视频">🎬 视频</button>
                    <button type="button" class="editor-btn" onclick="showFileModal()" title="插入文件">📄 文件</button>
                </div>
                <div id="editorContent" class="editor-content" contenteditable="true" placeholder="请输入文章内容"><?=!empty($edit_art['content']) ? (preg_match('/<[a-z][\s\S]*>/i', $edit_art['content']) ? $edit_art['content'] : nl2br(htmlspecialchars($edit_art['content'], ENT_NOQUOTES))) : ''?></div>
            </div>
            <input type="hidden" name="content" id="hiddenContent">
        </div>
        <?php if (!empty($edit_art)): ?>
        <div class="form-item">
            <label>当前点赞量</label>
            <input type="number" name="like_num" value="<?=$edit_art['like_num'] ?? 0?>" readonly style="background:#333; cursor:not-allowed;">
        </div>
        <div class="form-item">
            <label>创建时间</label>
            <input type="text" value="<?=$edit_art['create_time'] ?? ''?>" readonly style="background:#333; cursor:not-allowed;">
        </div>
        <?php endif; ?>
        <button type="submit" class="btn"><?=!empty($edit_art) ? '保存修改' : '新建文章'?></button>
    </form>
    <h2>文章列表</h2>
    <?php if (empty($article_list)): ?>
    <p style="color:#888; text-align:center;">暂无文章，快去创建第一篇吧！</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>文章标题</th>
                <th>创建时间</th>
                <th>点赞量</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($article_list as $art): ?>
            <tr>
                <td><?=mb_substr($art['title'], 0, 20) . (mb_strlen($art['title'])>20 ? '...' : '')?></td>
                <td><?=$art['create_time'] ?? ''?></td>
                <td><?=$art['like_num'] ?? 0?></td>
                <td>
                    <a href="adminui_plugins.php?plugin=blog&page=admin_articles&act=edit&id=<?=$art['art_id']?>" class="btn">编辑</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="act" value="del_art">
                        <input type="hidden" name="art_id" value="<?=$art['art_id']?>">
                        <button type="submit" class="btn del-btn" onclick="return confirm('确定删除该文章？删除后不可恢复！')">删除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <a href="adminui_plugins.php?plugin=blog&page=admin_overview" class="back">返回博客后台总览</a>
</div>
<div class="modal-overlay" id="linkModal">
    <div class="modal">
        <h3>插入链接</h3>
        <div class="modal-input">
            <label>链接地址</label>
            <input type="text" id="linkUrl" placeholder="请输入链接地址">
        </div>
        <div class="modal-input">
            <label>链接文本</label>
            <input type="text" id="linkText" placeholder="请输入链接显示文本">
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('linkModal')">取消</button>
            <button type="button" class="modal-btn modal-btn-primary" onclick="insertLink()">确定</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="imageModal">
    <div class="modal">
        <h3>插入图片</h3>
        <div class="upload-area" onclick="document.getElementById('imageFile').click()">
            <i>🖼️</i>
            <div class="upload-text">点击或拖拽上传图片</div>
        </div>
        <input type="file" id="imageFile" accept="image/*" style="display:none" onchange="uploadImage()">
        <div class="modal-input">
            <label>或输入图片URL</label>
            <input type="text" id="imageUrl" placeholder="请输入图片地址">
        </div>
        <div id="imagePreview"></div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('imageModal')">取消</button>
            <button type="button" class="modal-btn modal-btn-primary" onclick="insertImage()">确定</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="videoModal">
    <div class="modal">
        <h3>插入视频</h3>
        <div class="upload-area" onclick="document.getElementById('videoFile').click()">
            <i>🎬</i>
            <div class="upload-text">点击或拖拽上传视频</div>
        </div>
        <input type="file" id="videoFile" accept="video/*" style="display:none" onchange="uploadVideo()">
        <div class="modal-input">
            <label>或输入视频URL</label>
            <input type="text" id="videoUrl" placeholder="请输入视频地址">
        </div>
        <div id="videoPreview"></div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('videoModal')">取消</button>
            <button type="button" class="modal-btn modal-btn-primary" onclick="insertVideo()">确定</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="fileModal">
    <div class="modal">
        <h3>插入文件</h3>
        <div class="upload-area" onclick="document.getElementById('fileUpload').click()">
            <i>📄</i>
            <div class="upload-text">点击或拖拽上传文件</div>
        </div>
        <input type="file" id="fileUpload" style="display:none" onchange="uploadFile()">
        <div class="modal-input">
            <label>或输入文件URL</label>
            <input type="text" id="fileUrl" placeholder="请输入文件地址">
        </div>
        <div id="filePreview"></div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('fileModal')">取消</button>
            <button type="button" class="modal-btn modal-btn-primary" onclick="insertFile()">确定</button>
        </div>
    </div>
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
const editor = document.getElementById('editorContent');
const hiddenContent = document.getElementById('hiddenContent');
let savedRange = null;

function formatDoc(cmd, value = null) {
    document.execCommand(cmd, false, value);
    editor.focus();
}

function formatBlock(tag) {
    const tagMap = { 'h1': '<h1>', 'h2': '<h2>', 'p': '<p>' };
    document.execCommand('formatBlock', false, tagMap[tag] || '<p>');
    editor.focus();
}

function showLinkModal() {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    document.getElementById('linkModal').classList.add('show');
    document.getElementById('linkUrl').focus();
}

function showImageModal() {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    document.getElementById('imageModal').classList.add('show');
    document.getElementById('imageUrl').value = '';
    document.getElementById('imagePreview').innerHTML = '';
}

function showVideoModal() {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    document.getElementById('videoModal').classList.add('show');
    document.getElementById('videoUrl').value = '';
    document.getElementById('videoPreview').innerHTML = '';
}

function showFileModal() {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
    document.getElementById('fileModal').classList.add('show');
    document.getElementById('fileUrl').value = '';
    document.getElementById('filePreview').innerHTML = '';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function insertLink() {
    const url = document.getElementById('linkUrl').value.trim();
    const text = document.getElementById('linkText').value.trim();
    if (url) {
        editor.focus();
        const sel = window.getSelection();
        if (!savedRange) {
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            savedRange = range;
        }
        sel.removeAllRanges();
        sel.addRange(savedRange);
        if (sel.toString() || text) {
            const linkText = text || sel.toString();
            document.execCommand('insertHTML', false, '<a href="' + url + '" target="_blank">' + linkText + '</a>');
        } else {
            document.execCommand('createLink', false, url);
        }
        closeModal('linkModal');
        savedRange = null;
    } else {
        alert('请输入链接地址');
    }
}

function insertImage() {
    const url = document.getElementById('imageUrl').value.trim();
    if (url) {
        editor.focus();
        const sel = window.getSelection();
        if (!savedRange) {
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            savedRange = range;
        }
        sel.removeAllRanges();
        sel.addRange(savedRange);
        document.execCommand('insertImage', false, url);
        closeModal('imageModal');
        savedRange = null;
    } else {
        alert('请上传图片或输入图片URL');
    }
}

function insertVideo() {
    const url = document.getElementById('videoUrl').value.trim();
    if (url) {
        editor.focus();
        const sel = window.getSelection();
        if (!savedRange) {
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            savedRange = range;
        }
        sel.removeAllRanges();
        sel.addRange(savedRange);
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.style.maxWidth = '100%';
        document.execCommand('insertHTML', false, video.outerHTML);
        closeModal('videoModal');
        savedRange = null;
    } else {
        alert('请上传视频或输入视频URL');
    }
}

function insertFile() {
    const url = document.getElementById('fileUrl').value.trim();
    if (url) {
        editor.focus();
        const sel = window.getSelection();
        if (!savedRange) {
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            savedRange = range;
        }
        sel.removeAllRanges();
        sel.addRange(savedRange);
        const fileName = url.split('/').pop();
        document.execCommand('insertHTML', false, '<a href="' + url + '" target="_blank" download>' + fileName + '</a>');
        closeModal('fileModal');
        savedRange = null;
    } else {
        alert('请上传文件或输入文件URL');
    }
}

function uploadImage() {
    const fileInput = document.getElementById('imageFile');
    const file = fileInput.files[0];
    if (!file) return;
    const confirmBtn = document.querySelector('#imageModal .modal-btn-primary');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '上传中...';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', 'image');
    fetch('/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('imageUrl').value = data.url;
            document.getElementById('imagePreview').innerHTML = '<img src="' + data.url + '" class="preview-img" width="95%">';
        } else {
            alert('上传失败: ' + data.message);
        }
    })
    .catch(error => {
        alert('上传出错: ' + error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确定';
    });
}

function uploadVideo() {
    const fileInput = document.getElementById('videoFile');
    const file = fileInput.files[0];
    if (!file) return;
    const confirmBtn = document.querySelector('#videoModal .modal-btn-primary');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '上传中...';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', 'video');
    fetch('/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('videoUrl').value = data.url;
            document.getElementById('videoPreview').innerHTML = '<video src="' + data.url + '" controls class="preview-img" width="95%"></video>';
        } else {
            alert('上传失败: ' + data.message);
        }
    })
    .catch(error => {
        alert('上传出错: ' + error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确定';
    });
}

function uploadFile() {
    const fileInput = document.getElementById('fileUpload');
    const file = fileInput.files[0];
    if (!file) return;
    const confirmBtn = document.querySelector('#fileModal .modal-btn-primary');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '上传中...';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', 'file');
    fetch('/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('fileUrl').value = data.url;
            document.getElementById('filePreview').innerHTML = '<div style="padding:8px; background:#2a2a2a; border-radius:4px; font-size:12px;">📄 ' + data.url.split('/').pop() + '</div>';
        } else {
            alert('上传失败: ' + data.message);
        }
    })
    .catch(error => {
        alert('上传出错: ' + error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确定';
    });
}

document.getElementById('editorForm').addEventListener('submit', function(e) {
    const content = editor.innerHTML.trim();
    const textContent = content.replace(/<[^>]*>/g, '').trim();
    if (!textContent) {
        e.preventDefault();
        alert('文章内容不能为空！');
        editor.focus();
        return;
    }
    hiddenContent.value = content;
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });
});
</script>
</body>
</html>