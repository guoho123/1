<?php
/**
 * xf_glass_theme 禁用脚本
 */
$pluginDir = dirname(__FILE__);
if (is_dir($pluginDir . '/data')) {
    file_put_contents(
        $pluginDir . '/data/enable_log.txt',
        "Plugin disabled at: " . date('Y-m-d H:i:s') . "\n",
        FILE_APPEND
    );
}
