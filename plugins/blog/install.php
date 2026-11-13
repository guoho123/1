<?php
$pluginDir = dirname(__FILE__);
if (!file_exists($pluginDir . '/data')) {
    mkdir($pluginDir . '/data', 0777, true);
}

$config = [
    'installed_at' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'settings' => [
        'per_page' => 10,
        'show_excerpt' => true,
        'allow_likes' => true
    ]
];

file_put_contents($pluginDir . '/data/config.json', json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// 初始化本地博客数据文件 a3.json（已存在则不覆盖，保留历史文章数据）
require_once __DIR__ . '/data.php';
if (!file_exists(BLOG_DATA_FILE)) {
    blog_save_data(['views' => 0, 'articles' => []]);
}
