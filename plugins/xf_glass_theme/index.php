<?php
/**
 * xf_glass_theme 插件入口
 *
 * 通过钩子接管前台首页 UI（毛玻璃主题），不修改核心代码：
 * - head_assets : 注入主题 CSS 与覆盖样式（隐藏默认 .wrap 内容）
 * - body_start  : 注入完整主题 HTML（header / main / footer）
 * - body_end    : 注入主题数据与脚本（xf-common / xf-music-player）
 */
require_once __DIR__ . '/data.php';

global $pluginManager;

$assetUrl = '/plugins/xf_glass_theme/assets';

// ===== 首页接管：头部资源 =====
$pluginManager->registerHook('head_assets', function($data) {
    return $data
        . '<link rel="stylesheet" href="' . xf_theme_asset_url('a1.css') . '">'
        . '<link rel="stylesheet" href="https://player.xfyun.club/js/xf-Picture-Blackbox/xf-PictureBlackbox.min.css">'
        . '<style>'
        . '/* xf_glass_theme 主题接管：清除默认页面内边距（默认内容由 body_start 移入隐藏容器） */'
        . 'body.xf-glass-theme{padding:0!important;margin:0!important;}'
        . '</style>';
});

// ===== 首页接管：主题 HTML =====
$pluginManager->registerHook('body_start', function($data) {
    $config = xf_theme_load_config();
    // 关闭默认 .wrap，让主题内容独立于默认内容之外（默认内容在重新打开的隐藏 .wrap 中）
    $html = '</div>';
    $html .= '<script>document.body.classList.add("xf-glass-theme");</script>';
    $html .= '<div id="xf-theme-root">';
    // 隐藏占位按钮：让默认页面的主题切换脚本正常工作（不显示）
    $html .= '<button id="toggle" style="display:none"></button>';
    $html .= xf_theme_render_header('home', $config);
    $html .= '<main>';
    $html .= '<div class="right-col"><div id="profileArea"></div></div>';
    $html .= '<div class="left-col">';
    $html .= '<div class="glass time-card xf_time xf_glass">';
    $html .= '<div class="xf_clock"><div class="hand hours"></div><div class="hand minutes"></div><div class="hand seconds"></div><div class="point"></div>'
           . '<div class="marker"><span class="marker_time marker__1"></span><span class="marker_time marker__2"></span>'
           . '<span class="marker_time marker__3"></span><span class="marker_time marker__4"></span></div></div>';
    $html .= '<div class="display_time"><h3 class="xf_time_1">00:00</h3><h4 class="xf_time_2">0000年00月00日</h4><h5 class="xf_time_3">星期?</h5></div>';
    $html .= '</div>';
    $html .= '<div class="glass stat-grid" id="statGrid"></div>';
    $html .= '<div class="glass music-card" id="musicCard"></div>';
    $html .= '</div>';
    $html .= '</main>';
    $html .= xf_theme_render_footer($config);
    $html .= '</div>';
    // 重新打开 .wrap 装载默认内容（整体隐藏）
    $html .= '<div class="wrap" style="display:none">';
    return $data . $html;
});

// ===== 首页接管：数据与脚本 =====
$pluginManager->registerHook('body_end', function($data) {
    $config = xf_theme_load_config();
    return $data . xf_theme_render_scripts($config);
});

// ===== API 路由（公开） =====

// 获取主题配置
$pluginManager->registerApiRoute('xf_glass_theme_get_config', function($params) {
    return ['code' => 200, 'msg' => '获取成功', 'data' => xf_theme_load_config()];
});

// 获取项目列表
$pluginManager->registerApiRoute('xf_glass_theme_get_projects', function($params) {
    return ['code' => 200, 'msg' => '获取成功', 'data' => xf_theme_load_list('projects')];
});

// 获取文章列表
$pluginManager->registerApiRoute('xf_glass_theme_get_articles', function($params) {
    return ['code' => 200, 'msg' => '获取成功', 'data' => xf_theme_load_list('articles')];
});

// 获取项目/文章 Markdown 内容
$pluginManager->registerApiRoute('xf_glass_theme_get_md', function($params) {
    $type = ($params['type'] ?? '') === 'projects' ? 'projects' : 'articles';
    $file = $params['file'] ?? '';
    $content = xf_theme_read_md($type, $file);
    if ($content === null) {
        return ['code' => 404, 'msg' => '内容不存在'];
    }
    return ['code' => 200, 'msg' => '获取成功', 'data' => ['file' => $file, 'content' => $content]];
});

// 上传图片到插件 assets/img（仅管理员）
$pluginManager->registerApiRoute('xf_glass_theme_upload_img', function($params) {
    if (!isAdmin()) {
        return ['code' => 403, 'msg' => '无权限'];
    }
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
        return ['code' => 400, 'msg' => '未选择文件'];
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['code' => 400, 'msg' => '上传失败（错误码 ' . $file['error'] . '）'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['code' => 400, 'msg' => '图片不能超过 5MB'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'], true)) {
        return ['code' => 400, 'msg' => '仅支持 jpg/jpeg/png/gif/webp/ico 格式'];
    }
    $name = 'u' . date('Ymd') . '_' . substr(md5(uniqid('', true)), 0, 10) . '.' . $ext;
    $dir = __DIR__ . '/assets/img/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return ['code' => 500, 'msg' => '保存失败'];
    }
    return ['code' => 200, 'msg' => '上传成功', 'data' => ['url' => '/plugins/xf_glass_theme/assets/img/' . $name]];
});

// 上传全局头像（存 {APP_ROOT}/1/jpg/avatar{N+1}.jpg 并更新 a2.json 的 avatar_index，仅管理员）
$pluginManager->registerApiRoute('xf_glass_theme_upload_avatar', function($params) {
    if (!isAdmin()) {
        return ['code' => 403, 'msg' => '无权限'];
    }
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
        return ['code' => 400, 'msg' => '未选择文件'];
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['code' => 400, 'msg' => '上传失败（错误码 ' . $file['error'] . '）'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['code' => 400, 'msg' => '图片不能超过 5MB'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        return ['code' => 400, 'msg' => '仅支持 jpg/jpeg/png/gif 格式'];
    }
    $url = xf_theme_save_global_avatar($file['tmp_name']);
    if ($url === null) {
        return ['code' => 500, 'msg' => '保存失败'];
    }
    return ['code' => 200, 'msg' => '上传成功', 'data' => ['url' => $url]];
});
