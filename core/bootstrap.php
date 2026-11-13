<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/core/plugin.php';

$a1 = json_decode(file_get_contents(APP_ROOT . '/a1.json'), true) ?: [];
$a2 = json_decode(file_get_contents(APP_ROOT . '/a2.json'), true) ?: [];
$a3 = json_decode(file_get_contents(APP_ROOT . '/a3.json'), true) ?: ['views' => 0, 'articles' => []];

$SITE_NAME = $a1['site_name'] ?? '123';
$ADMIN_USER = $a1['admin_user'] ?? 'admin';
$ADMIN_PASS = $a1['admin_pwd'] ?? '123456';
$MAIL_CONFIG = $a1['mail_config'] ?? ['qq_mail' => '', 'port' => '', 'auth_code' => ''];

if (!function_exists('writeJson')) {
    function writeJson($file, $data) {
        $fp = fopen($file, 'w');
        flock($fp, LOCK_EX);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin_login']) && $_SESSION['admin_login'];
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isAdmin()) {
            header("Location: admin.php");
            exit;
        }
    }
}

if (!function_exists('renderHook')) {
    function renderHook($hookName, $data = '') {
        global $pluginManager;
        return $pluginManager->triggerHook($hookName, $data);
    }
}

if (!function_exists('registerHook')) {
    function registerHook($hookName, $callback) {
        global $pluginManager;
        $pluginManager->registerHook($hookName, $callback);
    }
}

if (!function_exists('registerApiRoute')) {
    function registerApiRoute($route, $callback) {
        global $pluginManager;
        $pluginManager->registerApiRoute($route, $callback);
    }
}