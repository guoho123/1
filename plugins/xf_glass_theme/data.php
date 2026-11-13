<?php
/**
 * xf_glass_theme 插件数据存取助手
 *
 * 所有主题数据存放在插件目录下的 data/ 目录，实现数据隔离、可热插拔：
 * - data/config.json    基础配置（logo文字/页脚/个人信息/社交按钮/其他网站/统计）
 * - data/projects.json  项目列表（manifest）
 * - data/articles.json  文章列表（manifest）
 * - data/md/*.md        项目与文章的 Markdown 内容
 */

if (!defined('XF_THEME_DATA_DIR')) {
    define('XF_THEME_DATA_DIR', __DIR__ . '/data');
}

/** 默认配置 */
function xf_theme_default_config() {
    $asset = '/plugins/xf_glass_theme/assets';
    return [
        'site_name' => '？？',
        'footer' => 'Copyright © {year} ？？. All Rights Reserved',
        'nav_home' => '？？',
        'nav_projects' => '？？',
        'nav_articles' => '？？',
        'nav_friends' => '友链',
        'friends' => [],
        'profile' => [
            'avatar' => 'https://picsum.photos/200/200',
            'name' => '？？',
            'desc' => "？？"
        ],
        'socials' => [
            [
                'type' => '？？',
                'text' => '<img alt="gitee" src="' . $asset . '/img/logo_gitee.png" style="height: 70%;">',
                'pic' => '',
                'popup' => '',
                'href' => 'https://gitee.com'
            ]
        ],
        'sites' => [
            [
                'text' => '？？',
                'href' => '',
                'cover' => $asset . '/img/shot2.png'
            ]
        ],
        'stats' => [
            'startDate' => '2024-03-01',
            'experienceStart' => '2024-03-01',
            'projectCount' => 0
        ]
    ];
}

/** 全局 a2.json 路径 */
function xf_theme_a2_path() {
    return defined('APP_ROOT') ? APP_ROOT . '/a2.json' : dirname(__DIR__, 2) . '/a2.json';
}

/** 读取全局 a2.json（数组） */
function xf_theme_load_a2() {
    $file = xf_theme_a2_path();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

/** 写全局 a2.json */
function xf_theme_save_a2($a2) {
    file_put_contents(xf_theme_a2_path(), json_encode($a2, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    return $a2;
}

/** 写全局简介列表（每行一条，空行过滤） */
function xf_theme_save_global_bio($descText) {
    $lines = preg_split('/\R/', trim((string)$descText));
    $lines = array_values(array_filter(array_map('trim', $lines), function($l){ return $l !== ''; }));
    $a2 = xf_theme_load_a2();
    $a2['bio_list'] = $lines;
    return xf_theme_save_a2($a2);
}

/** 上传全局头像：存 {APP_ROOT}/1/jpg/avatar{N+1}.jpg 并更新 avatar_index，返回 URL，失败返回 null */
function xf_theme_save_global_avatar($tmpFile) {
    $a2 = xf_theme_load_a2();
    $newIndex = (int)($a2['avatar_index'] ?? 0) + 1;
    $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
    $dir = $root . '/1/jpg/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $target = $dir . 'avatar' . $newIndex . '.jpg';
    if (!@move_uploaded_file($tmpFile, $target)) return null;
    $a2['avatar_index'] = $newIndex;
    xf_theme_save_a2($a2);
    return '1/jpg/avatar' . $newIndex . '.jpg';
}

/** 读取系统共享资料（a2.json：头像编号 + 简介列表，与默认首页 index.php 同一数据源） */
function xf_theme_system_profile() {
    $file = defined('APP_ROOT') ? APP_ROOT . '/a2.json' : dirname(__DIR__, 2) . '/a2.json';
    if (!file_exists($file)) return null;
    $a2 = json_decode(file_get_contents($file), true);
    if (!is_array($a2)) return null;
    $idx = (int)($a2['avatar_index'] ?? 0);
    $avatar = $idx > 0 ? '1/jpg/avatar' . $idx . '.jpg' : 'https://picsum.photos/200/200';
    $bios = $a2['bio_list'] ?? ['// 暂无简介，前往后台添加'];
    if (!is_array($bios)) $bios = [$bios];
    $bios = array_map(function($v){ return trim((string)$v); }, $bios);
    return [
        'avatar' => $avatar,
        'desc' => implode("\n", $bios)
    ];
}

/** 读取基础配置（自动补默认值；头像与简介跟随系统 a2.json） */
function xf_theme_load_config() {
    $file = XF_THEME_DATA_DIR . '/config.json';
    $config = [];
    if (file_exists($file)) {
        $config = json_decode(file_get_contents($file), true) ?: [];
    }
    $config = array_merge(xf_theme_default_config(), $config);
    $sys = xf_theme_system_profile();
    if ($sys) {
        $config['profile']['avatar'] = $sys['avatar'];
        $config['profile']['desc'] = $sys['desc'];
    }
    return $config;
}

/** 写入基础配置 */
function xf_theme_save_config($config) {
    if (!is_dir(XF_THEME_DATA_DIR)) mkdir(XF_THEME_DATA_DIR, 0777, true);
    file_put_contents(
        XF_THEME_DATA_DIR . '/config.json',
        json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
    return $config;
}

/** 读取列表（$type: projects | articles） */
function xf_theme_load_list($type) {
    $file = XF_THEME_DATA_DIR . '/' . $type . '.json';
    if (!file_exists($file)) return [];
    $list = json_decode(file_get_contents($file), true);
    return is_array($list) ? $list : [];
}

/** 写入列表（$type: projects | articles） */
function xf_theme_save_list($type, $list) {
    if (!in_array($type, ['projects', 'articles'], true) || !is_array($list)) return false;
    if (!is_dir(XF_THEME_DATA_DIR)) mkdir(XF_THEME_DATA_DIR, 0777, true);
    file_put_contents(
        XF_THEME_DATA_DIR . '/' . $type . '.json',
        json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
    return true;
}

/** md 类型与文件名校验（$type: projects | articles） */
function xf_theme_valid_type($type) {
    return in_array($type, ['projects', 'articles'], true);
}

function xf_theme_valid_md_name($file) {
    return is_string($file) && preg_match('/^[a-zA-Z0-9_\-]+\.md$/', $file) === 1;
}

/** 读取 md 内容，不存在返回 null */
function xf_theme_read_md($type, $file) {
    if (!xf_theme_valid_type($type) || !xf_theme_valid_md_name($file)) return null;
    $path = XF_THEME_DATA_DIR . '/md/' . $type . '/' . $file;
    if (!file_exists($path)) return null;
    return file_get_contents($path);
}

/** 写入 md 内容 */
function xf_theme_write_md($type, $file, $content) {
    if (!xf_theme_valid_type($type) || !xf_theme_valid_md_name($file)) return false;
    $dir = XF_THEME_DATA_DIR . '/md/' . $type;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return file_put_contents($dir . '/' . $file, $content, LOCK_EX) !== false;
}

/** 删除 md 文件 */
function xf_theme_delete_md($type, $file) {
    if (!xf_theme_valid_type($type) || !xf_theme_valid_md_name($file)) return false;
    $path = XF_THEME_DATA_DIR . '/md/' . $type . '/' . $file;
    if (file_exists($path)) return @unlink($path);
    return true;
}

/** 插件资源绝对 URL */
function xf_theme_asset_url($path = '') {
    return '/plugins/xf_glass_theme/assets' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** 前台插件页面 URL */
function xf_theme_page_url($page, $extra = '') {
    return 'index.php?plugin=xf_glass_theme&page=' . $page . ($extra !== '' ? '&' . ltrim($extra, '&') : '');
}

/** 输出主题头部公共 HTML（header 导航） */
function xf_theme_render_header($active, $config) {
    $home = 'index.php';
    $projects = xf_theme_page_url('projects');
    $articles = xf_theme_page_url('articles');
    $friends = xf_theme_page_url('friends');
    $siteName = htmlspecialchars($config['site_name'] ?: '小屋');
    $labelHome = htmlspecialchars($config['nav_home'] ?? '首页');
    $labelProjects = htmlspecialchars($config['nav_projects'] ?? '项目');
    $labelArticles = htmlspecialchars($config['nav_articles'] ?? '文章');
    $labelFriends = htmlspecialchars($config['nav_friends'] ?? '友链');
    $nav = '<a href="' . $home . '"' . ($active === 'home' ? ' class="active"' : '') . '>' . $labelHome . '</a>'
         . '<a href="' . $projects . '"' . ($active === 'projects' ? ' class="active"' : '') . '>' . $labelProjects . '</a>'
         . '<a href="' . $articles . '"' . ($active === 'articles' ? ' class="active"' : '') . '>' . $labelArticles . '</a>'
         . '<a href="' . $friends . '"' . ($active === 'friends' ? ' class="active"' : '') . '>' . $labelFriends . '</a>';
    return '<header>'
        . '<div class="logo">' . $siteName . '</div>'
        . '<button class="nav-toggle" id="navToggle" aria-label="菜单"><span></span><span></span><span></span></button>'
        . '<nav id="navMenu">' . $nav . '</nav>'
        . '</header>';
}

/** 输出主题公共脚部 HTML（footer） */
function xf_theme_render_footer($config) {
    $footer = str_replace('{year}', date('Y'), (string)($config['footer'] ?? ''));
    return '<footer><div>' . htmlspecialchars($footer) . '</div></footer>';
}

/** 输出右侧栏公共 HTML（头像区 + 时钟 + 统计 + 音乐） */
function xf_theme_render_side($config, $sitesArea = true) {
    $html = '<div class="right-col">'
        . '<div id="profileArea"></div>'
        . '<div class="glass time-card xf_time xf_glass">'
        . '<div class="xf_clock">'
        . '<div class="hand hours"></div><div class="hand minutes"></div><div class="hand seconds"></div><div class="point"></div>'
        . '<div class="marker">'
        . '<span class="marker_time marker__1"></span><span class="marker_time marker__2"></span>'
        . '<span class="marker_time marker__3"></span><span class="marker_time marker__4"></span>'
        . '</div></div>'
        . '<div class="display_time">'
        . '<h3 class="xf_time_1">00:00</h3>'
        . '<h4 class="xf_time_2">0000年00月00日</h4>'
        . '<h5 class="xf_time_3">星期?</h5>'
        . '</div></div>'
        . '<div class="glass stat-grid" id="statGrid"></div>'
        . '<div class="glass music-card" id="musicCard"></div>'
        . ($sitesArea ? '<div id="sitesArea"></div>' : '')
        . '</div>';
    return $html;
}

/** 输出公共 <head> 资源引用 */
function xf_theme_render_head_assets($css = 'a1.css') {
    return '<link rel="stylesheet" href="' . xf_theme_asset_url($css) . '">'
        . '<link rel="stylesheet" href="https://player.xfyun.club/js/xf-Picture-Blackbox/xf-PictureBlackbox.min.css">'
        . '<script src="' . xf_theme_asset_url('xf-page-transition.js') . '"></script>';
}

/** 输出公共底部脚本（数据注入 + xf-common + 音乐播放器） */
function xf_theme_render_scripts($config) {
    $cfgJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    return '<script>window.__XF_THEME_CFG=' . $cfgJson . ';</script>'
        . '<script src="' . xf_theme_asset_url('xf-common.js') . '"></script>'
        . '<script src="' . xf_theme_asset_url('xf-music-player.js') . '"></script>';
}
