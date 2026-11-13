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
