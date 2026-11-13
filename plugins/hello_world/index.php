<?php
global $pluginManager;

$pluginManager->registerHook('head_assets', function($data) {
    return $data . "\n<!-- Hello World Plugin CSS -->\n<style>.hello-world { color: #455A64; font-weight: bold; }</style>";
});

$pluginManager->registerHook('body_end', function($data) {
    return $data . "\n<!-- Hello World Plugin Footer -->\n<div class=\"hello-world\" style=\"text-align:center; padding:20px; background:#1a1a1a; border-top:1px solid #333; margin-top:40px;\">你可能还未配置，请前往后台进行配置配置完成之后关闭此插件</div>";
});
