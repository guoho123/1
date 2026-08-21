// xf-player.js —— 音乐播放器懒加载 + 初始化（防重复）
(function() {
    var SCRIPT_URL = 'https://player.xfyun.club/js/music-player/music-player.min.js';
    var CONFIG = {
        language: 'zh',
        theme: 'xf-original-theme',
        mode: 'cloud',
        apiUrl: 'https://music.api.xfyun.club/api/v1/music/top?platform=netease&topId=3778678',
        autoPopup: true
    };

    // ---------- 初始化播放器（使用自定义元素方式） ----------
    function initPlayer() {
        // 如果已经有播放器元素，就不再创建
        if (document.querySelector('xf-music-player')) {
            console.log('[xf-player] 播放器元素已存在，无需重复创建');
            return;
        }

        var el = document.createElement('xf-music-player');
        el.setAttribute('language', CONFIG.language);
        el.setAttribute('theme', CONFIG.theme);
        el.setAttribute('mode', CONFIG.mode);
        el.setAttribute('api-url', CONFIG.apiUrl);
        if (CONFIG.autoPopup) {
            el.setAttribute('is-auto-popup', 'true');
        }
        // 将播放器追加到 body 末尾（若想放到特定容器，可修改此处）
        document.body.appendChild(el);
        console.log('[xf-player] 播放器元素已插入');
    }

    // ---------- 主流程：确保脚本加载一次后初始化 ----------
    function loadAndInit() {
        // 1. 判断脚本是否已加载（自定义元素 xf-message 是库注册的）
        if (customElements.get('xf-message')) {
            initPlayer();
            return;
        }

        // 2. 防止并发加载
        if (window.__xfPlayerLoading) {
            // 如果正在加载，等待加载完成
            var checkExist = setInterval(function() {
                if (customElements.get('xf-message')) {
                    clearInterval(checkExist);
                    initPlayer();
                }
            }, 200);
            return;
        }

        // 3. 动态加载脚本
        window.__xfPlayerLoading = true;
        var script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = function() {
            window.__xfPlayerLoading = false;
            initPlayer();
        };
        script.onerror = function() {
            window.__xfPlayerLoading = false;
            console.error('[xf-player] 播放器脚本加载失败');
        };
        document.head.appendChild(script);
    }

    // ---------- 在 DOM 就绪后执行 ----------
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndInit);
    } else {
        loadAndInit();
    }
})();