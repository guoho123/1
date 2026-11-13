<?php
$pluginDir = dirname(__FILE__);
$configFile = $pluginDir . '/data/config.json';
if (file_exists($configFile)) {
    unlink($configFile);
}
$dataDir = $pluginDir . '/data';
if (file_exists($dataDir)) {
    rmdir($dataDir);
}
// 清理本地博客数据文件 a3.json（含全部文章与访问数据，卸载后不可恢复）
$dataFile = $pluginDir . '/a3.json';
if (file_exists($dataFile)) {
    unlink($dataFile);
}
