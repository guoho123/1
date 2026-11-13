<?php
/**
 * xf_glass_theme 启用脚本
 */
$pluginDir = dirname(__FILE__);
if (!is_dir($pluginDir . '/data')) {
    mkdir($pluginDir . '/data', 0777, true);
}
file_put_contents(
    $pluginDir . '/data/enable_log.txt',
    "Plugin enabled at: " . date('Y-m-d H:i:s') . "\n",
    FILE_APPEND
);
