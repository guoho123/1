<?php
/**
 * xf_glass_theme 卸载脚本：清理插件数据
 */
$pluginDir = dirname(__FILE__);
$dataDir = $pluginDir . '/data';

// 递归删除 data 目录
function xf_theme_rrmdir($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return @unlink($dir);
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        is_dir($path) ? xf_theme_rrmdir($path) : @unlink($path);
    }
    return @rmdir($dir);
}

xf_theme_rrmdir($dataDir);
