<?php
$pluginDir = dirname(__FILE__);
$logFile = $pluginDir . '/data/enable_log.txt';
file_put_contents($logFile, "Plugin enabled at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
