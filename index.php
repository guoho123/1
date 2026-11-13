<?php
require 'core/bootstrap.php';

if (isset($_GET['plugin'])) {
    $pluginName = trim($_GET['plugin']);
    $page = trim($_GET['page'] ?? '');
    $pluginsDir = APP_ROOT . '/plugins/';
    if (file_exists($pluginsDir . $pluginName)) {
        $pageMap = [
            'frontend' => 'frontend.php',
            'admin_overview' => 'admin_overview.php',
            'admin_articles' => 'admin_articles.php',
            'admin' => 'admin.php'
        ];
        $pageFile = $pageMap[$page] ?? ($page . '.php');
        if (file_exists($pluginsDir . $pluginName . '/' . $pageFile)) {
            include $pluginsDir . $pluginName . '/' . $pageFile;
            exit;
        }
    }
    header("Location: index.php");
    exit;
}

$avatarIndex = $a2['avatar_index'] ?? 0;
$bioList = $a2['bio_list'] ?? ['// 暂无简介，前往后台添加'];
$linkList = $a2['link_list'] ?? [];
$avatarUrl = $avatarIndex > 0 ? '1/jpg/avatar'.$avatarIndex.'.jpg' : 'https://picsum.photos/200/200';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=$SITE_NAME?> - 个人主页</title>
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background 0.3s, color 0.3s, border-color 0.3s;
  }
  :root {
    --bg: #0a0a0a;
    --card: #121212;
    --text: #e0e0e0;
    --sub: #888;
    --line: #333;
    --accent: linear-gradient(90deg, #fff, #aaa);
    --border: #444;
    --btn-bg: #1a1a1a;
  }
  .light {
    --bg: #ffffff;
    --card: #f5f5f5;
    --text: #1a1a1a;
    --sub: #666;
    --line: #ddd;
    --accent: linear-gradient(90deg, #000, #444);
    --border: #ccc;
    --btn-bg: #f0f0f0;
  }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: "Segoe UI", Roboto, monospace;
    min-height: 100vh;
    padding: 5rem 1.5rem;
    background-image: 
      linear-gradient(rgba(0,0,0,0.02), rgba(0,0,0,0.02)),
      url("data:image/svg+xml,%3Csvg width='30' height='30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h30v30H0z' fill='%23000' fill-opacity='0.03'/%3E%3Cpath d='M0 0l30 30M30 0L0 30' stroke='%23000' stroke-width='0.3' stroke-opacity='0.1'/%3E%3C/svg%3E");
  }
  .wrap {
    max-width: 780px;
    margin: 0 auto;
    position: relative;
  }
  .theme-toggle {
    position: absolute;
    top: -3rem;
    right: 0;
    padding: 0.6rem 1.2rem;
    background: var(--btn-bg);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    font-size: 0.9rem;
    border-radius: 6px;
  }
  .theme-toggle:hover {
    border-color: #777;
  }
  /* 头像区域 */
  .avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border);
    margin-bottom: 1.5rem;
  }
  h1 {
    font-size: 3.4rem;
    font-weight: 700;
    background: var(--accent);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.6rem;
  }
  .title {
    font-size: 1.1rem;
    color: var(--sub);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 2.5rem;
  }
  .cyber-line {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 2.5rem 0;
  }
  .bio-block {
    border-left: 2px solid var(--border);
    padding: 1.4rem 1.6rem;
    background: var(--card);
    margin: 3rem 0;
    border-radius: 0 6px 6px 0;
  }
  .bio-block p {
    line-height: 1.7;
    margin-bottom: 0.6rem;
    color: var(--text);
  }
  .bio-block p:last-child {
    margin-bottom: 0;
  }
  .links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 3rem;
  }
  .btn {
    padding: 1rem 2rem;
    background: var(--btn-bg);
    border: 1px solid var(--border);
    color: var(--text);
    text-decoration: none;
    font-size: 0.95rem;
    border-radius: 6px;
  }
  .btn:hover {
    border-color: #777;
    background: var(--card);
  }
  .foot {
    margin-top: 7rem;
    font-size: 0.8rem;
    color: var(--sub);
    text-align: center;
  }
  /* 移动端适配 */
  @media (max-width: 480px) {
    body {
      padding: 4rem 1rem;
    }
    h1 {
      font-size: 2.2rem;
    }
    .links {
      gap: 0.8rem;
    }
    .btn {
      padding: 0.8rem 1.5rem;
      font-size: 0.9rem;
      flex: 1;
      text-align: center;
    }
  }
</style>
<?php echo $pluginManager->triggerHook('head_assets'); ?>
</head>
<body>
<div class="wrap">
  <?php echo $pluginManager->triggerHook('body_start'); ?>
  <button class="theme-toggle" id="toggle">MODE ↻</button>
  <!-- 头像 -->
  <img class="avatar" src="<?=$avatarUrl?>" alt="个人头像">
  <h1><?=$SITE_NAME?></h1>
  <div class="title">Personal Profile</div>
  <div class="cyber-line"></div>
  <!-- 简介区域，从a2.json读取 -->
  <div class="bio-block">
    <?php foreach ($bioList as $bio): ?>
    <p><?=htmlspecialchars($bio)?></p>
    <?php endforeach; ?>
  </div>
  <?php echo $pluginManager->triggerHook('bio_after'); ?>
  <div class="cyber-line"></div>
  <!-- 外链区域，从a2.json读取 -->
  <div class="links">
    <?php echo $pluginManager->triggerHook('front_nav'); ?>
    <?php foreach ($linkList as $link): ?>
    <a href="<?=htmlspecialchars($link['url'])?>" class="btn" target="_blank"><?=htmlspecialchars($link['name'])?></a>
    <?php endforeach; ?>
  </div>
  <!-- 底部 -->
  <div class="foot">© <?php echo date('Y'); ?> // <?=$SITE_NAME?> - Personal System</div>
</div>

<script>
  // 主题切换逻辑，状态持久化到本地存储
  const toggle = document.getElementById('toggle');
  const html = document.documentElement;
  
  // 初始化主题：读取本地存储，无则默认暗色
  if (localStorage.getItem('theme') === 'light') {
    html.classList.add('light');
    toggle.innerText = 'DARK ↻';
  } else {
    html.classList.remove('light');
    toggle.innerText = 'LIGHT ↻';
  }
  
  // 点击切换主题
  toggle.addEventListener('click', () => {
    html.classList.toggle('light');
    if (html.classList.contains('light')) {
      localStorage.setItem('theme', 'light');
      toggle.innerText = 'DARK ↻';
    } else {
      localStorage.setItem('theme', 'dark');
      toggle.innerText = 'LIGHT ↻';
    }
  });
</script>
<?php echo $pluginManager->triggerHook('body_end'); ?>
</body>
</html>
