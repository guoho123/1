<?php
/**
 * Blog 插件本地数据存储助手
 *
 * 将所有博客数据存放在插件目录下的 a3.json，使插件完全自包含、可热插拔，
 * 不再依赖宿主应用的 $a3 全局变量与根目录 writeJson。
 */

if (!defined('BLOG_DATA_FILE')) {
    define('BLOG_DATA_FILE', __DIR__ . '/a3.json');
}

/**
 * 读取本地 a3.json，返回标准化数组。
 *
 * @return array {views: int, articles: array}
 */
function blog_load_data() {
    if (!file_exists(BLOG_DATA_FILE)) {
        $empty = ['views' => 0, 'articles' => []];
        blog_save_data($empty);
        return $empty;
    }
    $raw  = file_get_contents(BLOG_DATA_FILE);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['views' => 0, 'articles' => []];
    }
    if (!isset($data['articles']) || !is_array($data['articles'])) {
        $data['articles'] = [];
    }
    if (!isset($data['views']) || !is_numeric($data['views'])) {
        $data['views'] = 0;
    }
    return $data;
}

/**
 * 写入本地 a3.json。
 *
 * @param array $data
 * @return array 写入并标准化后的数据
 */
function blog_save_data($data) {
    if (!isset($data['articles']) || !is_array($data['articles'])) {
        $data['articles'] = [];
    }
    if (!isset($data['views']) || !is_numeric($data['views'])) {
        $data['views'] = 0;
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents(BLOG_DATA_FILE, $json, LOCK_EX);
    return $data;
}
