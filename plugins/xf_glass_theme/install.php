<?php
/**
 * xf_glass_theme 安装脚本：初始化数据目录与默认数据
 */
$pluginDir = dirname(__FILE__);
require_once $pluginDir . '/data.php';

// 初始化基础配置
$configFile = $pluginDir . '/data/config.json';
if (!file_exists($configFile)) {
    xf_theme_save_config(xf_theme_default_config());
}

// 初始化默认项目与文章
if (!file_exists($pluginDir . '/data/projects.json')) {
    xf_theme_write_md('projects', 'welcome-project.md', "# 欢迎使用小屋玻璃主题\n\n在后台「小屋玻璃主题 → 项目管理」中添加你的项目。\n\n- 支持 **Markdown** 语法\n- 每个项目包含标题、日期、摘要、封面与内容\n");
    xf_theme_save_list('projects', [
        [
            'title' => '欢迎使用项目系统',
            'file' => 'welcome-project.md',
            'date' => date('Y-m-d'),
            'summary' => '在后台「项目管理」中添加你的项目，此页面会自动展示。',
            'cover' => '/plugins/xf_glass_theme/assets/img/shot3.png'
        ]
    ]);
}
if (!file_exists($pluginDir . '/data/articles.json')) {
    xf_theme_write_md('articles', 'welcome-article.md', "# 欢迎使用小屋玻璃主题\n\n在后台「小屋玻璃主题 → 文章管理」中撰写你的文章。\n\n## 支持的语法\n\n- 标题、列表、引用\n- **加粗**、*斜体*、`代码`\n- 图片与链接\n");
    xf_theme_save_list('articles', [
        [
            'title' => '欢迎使用文章系统',
            'file' => 'welcome-article.md',
            'date' => date('Y-m-d'),
            'summary' => '在后台「文章管理」中撰写 Markdown 文章，此页面会自动展示。',
            'cover' => '/plugins/xf_glass_theme/assets/img/shot2.png'
        ]
    ]);
}
