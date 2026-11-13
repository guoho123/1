<?php
require 'core/bootstrap.php';
requireAdmin();
$avatarIndex = $a2['avatar_index'] ?? 0;
$bioList = $a2['bio_list'] ?? [];
$linkList = $a2['link_list'] ?? [];
$msg = '';

// 头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'upload_avatar') {
    // 直接在当前页面处理上传，不再通过curl转发，避免文件上传问题
    $a2 = json_decode(file_get_contents('a2.json'), true) ?: [];
    $index = $a2['avatar_index'] ?? 0;
    $newIndex = $index + 1;
    $targetDir = '1/jpg/';
    
    // 检查并创建目录
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $file = $_FILES['avatar'] ?? [];
    if (empty($file) || $file['error'] !== 0) {
        $msg = '头像上传失败，文件未正确接收';
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowExt)) {
            $msg = '仅支持jpg/png/gif格式';
        } else {
            $targetFile = $targetDir . 'avatar' . $newIndex . '.jpg';
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $a2['avatar_index'] = $newIndex;
                file_put_contents('a2.json', json_encode($a2, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
                $msg = '头像上传成功';
                header("Refresh:1;url=adminui2.php");
                exit;
            } else {
                $msg = '文件移动失败，请检查目录权限';
            }
        }
    }
}

// 保存简介
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save_bio') {
    // 使用PHP_EOL或"\n"来正确分割换行
    $bio_content = trim($_POST['bio_content'] ?? '');
    $bio_list = preg_split('/\R/', $bio_content); // 使用正则分割所有类型的换行符
    $bio_list = array_filter($bio_list, function($line) {
        return trim($line) !== ''; // 过滤空行
    });
    
    $data = ['act' => 'update_bio', 'bio_list' => $bio_list];
    
    // 直接在当前页面处理，不再通过curl转发，避免数组序列化问题
    $a2 = json_decode(file_get_contents('a2.json'), true) ?: [];
    $a2['bio_list'] = $bio_list;
    if (file_put_contents('a2.json', json_encode($a2, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))) {
        $msg = '简介保存成功';
        header("Refresh:1;url=adminui2.php");
        exit;
    } else {
        $msg = '简介保存失败';
    }
}

// 保存链接
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save_link') {
    $link_names = $_POST['link_name'] ?? [];
    $link_urls = $_POST['link_url'] ?? [];
    $link_list = [];
    for ($i=0; $i<count($link_names); $i++) {
        if (!empty($link_names[$i]) && !empty($link_urls[$i])) {
            $link_list[] = [
                'name' => trim($link_names[$i]),
                'url' => trim($link_urls[$i])
            ];
        }
    }
    $data = ['act' => 'update_link', 'link_list' => json_encode($link_list)];
    $ch = curl_init('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'/../API.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if ($res['code'] === 200) {
       $msg = '保存成功';
        header("Refresh:1;url=adminui2.php");
        exit;
    } else {
   $msg = '保存成功';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>个人网页管理-<?=$SITE_NAME?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#000; color:#fff; font-family:system-ui, sans-serif; padding:24px; min-height:100vh; transition:all 0.3s; }
body.light { background:#F3F2EF; color:#2D2D2D; }
.container { max-width:800px; width:100%; margin:0 auto; background:#121212; padding:40px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.5); transition:all 0.3s; }
body.light .container { background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); }
h1 { text-align:center; margin-bottom:40px; color:#455A64; }
h2 { margin:30px 0 20px; font-size:18px; border-left:4px solid #455A64; padding-left:10px; }
.form-item { margin-bottom:20px; }
label { display:block; margin-bottom:8px; font-size:14px; }
input, textarea { width:100%; padding:12px; border:1px solid #333; border-radius:6px; background:#1a1a1a; color:#fff; font-size:14px; transition:all 0.3s; }
body.light input, body.light textarea { background:#FAFAFA; color:#2D2D2D; border:1px solid #DCDCDC; }
textarea { min-height:150px; resize:vertical; }
.btn { padding:10px 20px; border:none; border-radius:6px; background:#455A64; color:#fff; cursor:pointer; font-size:14px; }
.btn:hover { background:#37474F; }
.msg { padding:12px; border-radius:6px; margin-bottom:20px; text-align:center; }
.success { background:#E8F5E9; color:#2E7D32; }
.error { background:#FFEBEE; color:#C62828; }
.back { display:block; text-align:center; margin-top:30px; color:#455A64; text-decoration:none; font-size:14px; }
.avatar-preview { width:100px; height:100px; border-radius:50%; border:2px solid #455A64; margin:10px 0; object-fit:cover; }
.link-item { display:flex; gap:10px; margin-bottom:10px; align-items:center; }
.link-item input { flex:1; }
.link-item .del { background:#D32F2F; padding:8px 12px; border-radius:6px; cursor:pointer; }
.add-link { background:#2E7D32; padding:8px 12px; border-radius:6px; cursor:pointer; display:inline-block; margin-top:10px; }
.theme-toggle { position:fixed; top:20px; right:20px; padding:8px 16px; border:1px solid #333; background:#121212; color:#fff; cursor:pointer; border-radius:6px; }
body.light .theme-toggle { background:#fff; border:1px solid #DCDCDC; color:#2D2D2D; }
</style>
</head>
<body>
<button class="theme-toggle" id="toggle">切换亮色</button>
<div class="container">
    <h1>个人网页管理</h1>
    <?php if ($msg): ?>
    <div class="msg <?php echo strpos($msg, '成功')!==false ? 'success' : 'error'; ?>"><?=$msg?></div>
    <?php endif; ?>

    <!-- 头像管理 -->
    <h2>头像上传</h2>
    <?php if ($avatarIndex > 0): ?>
    <img src="1/jpg/avatar<?=$avatarIndex?>.jpg" class="avatar-preview" alt="当前头像">
    <p>当前头像：avatar<?=$avatarIndex?>.jpg</p>
    <?php else: ?>
    <p>暂无头像，请上传</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="act" value="upload_avatar">
        <div class="form-item">
            <label>选择头像（jpg/png/gif）</label>
            <input type="file" name="avatar" accept="image/jpg,image/png,image/gif" required>
        </div>
        <button type="submit" class="btn">上传并替换</button>
    </form>

    <!-- 简介管理 -->
    <h2>简介管理</h2>
    <form method="post">
        <input type="hidden" name="act" value="save_bio">
        <div class="form-item">
            <label>简介内容（每行一条）</label>
            <textarea name="bio_content" required><?=implode("\n", $bioList)?></textarea>
        </div>
        <button type="submit" class="btn">保存简介</button>
    </form>

    <!-- 链接管理 -->
    <h2>外链管理</h2>
    <form method="post" id="linkForm">
        <input type="hidden" name="act" value="save_link">
        <div id="linkContainer">
            <?php foreach ($linkList as $link): ?>
            <div class="link-item">
                <input type="text" name="link_name[]" value="<?=$link['name']?>" placeholder="链接名称" required>
                <input type="url" name="link_url[]" value="<?=$link['url']?>" placeholder="链接地址" required>
                <div class="del" onclick="delLink(this)">删除</div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="add-link" onclick="addLink()">添加链接</div>
        <button type="submit" class="btn" style="margin-top:20px;">保存链接</button>
    </form>

    <a href="adminui.php" class="back">返回总导航</a>
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
// 链接管理
function addLink() {
    const container = document.getElementById('linkContainer');
    const item = document.createElement('div');
    item.className = 'link-item';
    item.innerHTML = `
        <input type="text" name="link_name[]" placeholder="链接名称" required>
        <input type="url" name="link_url[]" placeholder="链接地址" required>
        <div class="del" onclick="delLink(this)">删除</div>
    `;
    container.appendChild(item);
}
function delLink(el) {
    el.parentElement.remove();
}
</script>
</body>
</html>
