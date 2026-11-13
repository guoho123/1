<?php
/**
 * xf_glass_theme 更新脚本：数据迁移与配置升级
 */
$pluginDir = dirname(__FILE__);
require_once $pluginDir . '/data.php';

$configFile = $pluginDir . '/data/config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (is_array($config)) {
        $changed = false;
        // 补齐新版本新增字段
        foreach (['nav_home', 'nav_projects', 'nav_articles'] as $key) {
            if (!isset($config[$key])) {
                $defaults = xf_theme_default_config();
                $config[$key] = $defaults[$key];
                $changed = true;
            }
        }
        if ($changed) {
            xf_theme_save_config($config);
        }
    }
}
