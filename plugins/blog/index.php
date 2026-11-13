<?php
require_once __DIR__ . '/data.php';
global $pluginManager;

$pluginManager->registerHook('front_nav', function($data) {
    return $data . '<a href="index.php?plugin=blog&page=frontend" class="btn" target="_blank">博客主页</a>';
});

$pluginManager->registerHook('admin_nav', function($data) {
    return $data . '<a href="adminui_plugins.php?plugin=blog&page=admin_overview" class="nav-card"><span class="icon">📝</span><h2>博客后台管理</h2><p>数据总览、文章列表管理</p></a>';
});

$pluginManager->registerHook('head_assets', function($data) {
    return $data;
});

$pluginManager->registerApiRoute('blog_get_articles', function($params) {
    $a3 = blog_load_data();
    $articles = $a3['articles'];
    $page = isset($params['page']) ? (int)$params['page'] : 1;
    $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $total = count($articles);
    $paginated = array_slice($articles, $offset, $limit);
    return ['code' => 200, 'msg' => '获取成功', 'data' => [
        'articles' => $paginated,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]];
});

$pluginManager->registerApiRoute('blog_get_article', function($params) {
    $a3 = blog_load_data();
    $articleId = $params['id'] ?? '';
    $articles = $a3['articles'];
    foreach ($articles as $art) {
        if ($art['art_id'] === $articleId) {
            return ['code' => 200, 'msg' => '获取成功', 'data' => $art];
        }
    }
    return ['code' => 404, 'msg' => '文章不存在'];
});

$pluginManager->registerApiRoute('blog_like', function($params) {
    $a3 = blog_load_data();
    $articleId = $params['id'] ?? '';
    $articles = $a3['articles'];
    foreach ($articles as $k => $v) {
        if ($v['art_id'] === $articleId) {
            $articles[$k]['like_num'] = ($v['like_num'] ?? 0) + 1;
            $a3['articles'] = $articles;
            blog_save_data($a3);
            return ['code' => 200, 'msg' => '点赞成功', 'data' => ['like_num' => $articles[$k]['like_num']]];
        }
    }
    return ['code' => 404, 'msg' => '文章不存在'];
});

$pluginManager->registerApiRoute('blog_get_stats', function($params) {
    $a3 = blog_load_data();
    $articles = $a3['articles'];
    $likeTotal = 0;
    foreach ($articles as $art) {
        $likeTotal += $art['like_num'] ?? 0;
    }
    return ['code' => 200, 'msg' => '获取成功', 'data' => [
        'views' => $a3['views'] ?? 0,
        'articles' => count($articles),
        'likes' => $likeTotal
    ]];
});
