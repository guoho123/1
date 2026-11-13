<?php
$pluginDir = dirname(__FILE__);
$configFile = $pluginDir . '/data/config.json';

if (!file_exists($pluginDir . '/data')) {
    mkdir($pluginDir . '/data', 0777, true);
}

file_put_contents($configFile, json_encode([
    'installed_at' => date('Y-m-d H:i:s'),
    'version' => '1.0.0'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
