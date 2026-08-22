// xf-music-player.js —— 热歌榜音乐播放器（热插拔插件，pjax 无缝切换）
// 持久层：音频元素 + 状态存放在 body 末尾 #xfMusicPersistent，pjax 只替换 <main>，音频不中断
// 视图层：#musicCard 内的 UI 每次 pjax 后由 MutationObserver 触发 init() 重建并从持久状态恢复
// API: https://node.api.xfabe.com/api/wangyi/musicChart?list=热歌榜
(function(){
    // ===== 样式注入 =====
    var CSS = ''+
    '.music-card{position:relative;margin-bottom:16px;}'+
    '.xf_music_top{display:flex;align-items:center;gap:20px;}'+
    '.music-disc{position:relative;width:110px;height:110px;border-radius:50%;'+
        'background:radial-gradient(circle, rgba(255,255,255,0.18), rgba(255,255,255,0.05));'+
        'box-shadow:0 0 4px 2px rgba(0,0,0,0.25), inset 0 0 6px rgba(255,255,255,0.15);'+
        'flex-shrink:0;overflow:hidden;display:flex;justify-content:center;align-items:center;}'+
    '.music-cover{width:80%;height:80%;border-radius:50%;object-fit:cover;'+
        'border:2px solid rgba(255,255,255,0.2);background:rgba(0,0,0,0.25);}'+
    '.music-disc.playing .music-cover{animation:xf-music-spin 8s linear infinite;}'+
    '@keyframes xf-music-spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}'+
    '.music-info{display:flex;flex-direction:column;color:#fff;'+
        'text-shadow:0 1px 2px rgba(0,0,0,0.3);min-width:0;flex:1;}'+
    '.music-name{font-size:16px;font-weight:500;margin:2px 0;'+
        'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'+
    '.music-artist{font-size:13px;font-weight:normal;color:#bbbbbb;margin:2px 0;'+
        'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'+
    '.music-controls{display:flex;gap:12px;margin-top:14px;justify-content:center;}'+
    '.music-btn{width:36px;height:36px;border-radius:50%;'+
        'border:1px solid rgba(255,255,255,0.18);background:rgba(255,255,255,0.06);'+
        'color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;'+
        'font-size:16px;line-height:1;padding:0;transition:background .2s,transform .2s;}'+
    '.music-btn:hover{background:rgba(255,255,255,0.18);transform:scale(1.08);}'+
    '.music-play-btn{width:44px;height:44px;font-size:18px;}'+
    '.music-menu-btn{position:absolute;top:12px;right:12px;width:28px;height:28px;'+
        'border-radius:8px;border:1px solid rgba(255,255,255,0.18);'+
        'background:rgba(255,255,255,0.08);color:#fff;cursor:pointer;'+
        'display:flex;align-items:center;justify-content:center;font-size:16px;z-index:10;}'+
    '.music-menu-btn:hover{background:rgba(255,255,255,0.18);}'+
    '.music-menu-btn.active{background:rgba(130,184,255,0.25);}'+
    '.music-menu-panel{position:absolute;top:48px;right:8px;width:260px;max-height:320px;'+
        'overflow-y:auto;background:rgba(20, 20, 30, 0.51);'+
        'backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);'+
        'border:1px solid rgba(255,255,255,0.15);border-radius:12px;padding:10px;z-index:20;'+
        'box-shadow:0 8px 24px rgba(0,0,0,0.4);}'+
    '.music-menu-panel h5{font-size:13px;color:#aaa;margin-bottom:8px;padding:0 4px;font-weight:normal;}'+
    '.music-song-list{list-style:none;margin:0;padding:0;}'+
    '.music-song-list li{padding:8px 10px;border-radius:6px;cursor:pointer;font-size:13px;color:#ddd;'+
        'display:flex;flex-direction:column;gap:2px;transition:background .15s;}'+
    '.music-song-list li:hover{background:rgba(255,255,255,0.08);}'+
    '.music-song-list li.active{background:rgba(130,184,255,0.18);color:#fff;}'+
    '.music-song-list li .li-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'+
    '.music-song-list li .li-artist{font-size:11px;color:#888;'+
        'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}';

    var st = document.createElement('style');
    st.id = 'xf-music-player';
    st.textContent = CSS;
    (document.head || document.documentElement).appendChild(st);

    // ===== 常量 =====
    var API_URL = 'https://node.api.xfabe.com/api/wangyi/musicChart?list=' + encodeURIComponent('热歌榜');
    var CACHE_KEY = 'xf_music_chart_v1';

    // ===== 持久状态（pjax 切换不丢失） =====
    var songs = [];
    var currentIndex = -1;
    var isPlaying = false;
    var chartTitle = '歌曲目录';
    var audio = null;        // 持久 <audio>，位于 #xfMusicPersistent
    var audioReady = false;  // 持久层是否已创建
    var el = {};             // 当前视图元素（每次 init 重建）

    // ===== 持久层：在 body 末尾创建隐藏 <audio>（只创建一次） =====
    function setupPersistent(){
        if (audioReady) {
            if (!audio) audio = document.getElementById('musicAudio');
            return;
        }
        var holder = document.createElement('div');
        holder.id = 'xfMusicPersistent';
        holder.style.cssText = 'position:absolute;left:-9999px;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none;';
        holder.innerHTML = '<audio id="musicAudio" preload="metadata"></audio>';
        document.body.appendChild(holder);
        audio = holder.querySelector('#musicAudio');
        audioReady = true;

        // 持久事件：音频状态变化 → 同步到当前视图（el 动态引用，永远指向最新视图）
        audio.addEventListener('ended', next);
        audio.addEventListener('play', function(){ setPlaying(true); });
        audio.addEventListener('pause', function(){ setPlaying(false); });
        audio.addEventListener('error', function(){
            console.warn('音频加载失败，自动跳过');
            setPlaying(false);
            setTimeout(next, 500);
        });

        // 全局：点击卡片外部关闭菜单（el.card 动态引用）
        document.addEventListener('click', function(e){
            if (!el.card) return;
            if (!el.card.contains(e.target)) closeMenu();
        });
    }

    // ===== 视图骨架（不含 <audio>，音频在持久层） =====
    function renderSkeleton(container){
        container.innerHTML =
            '<button class="music-menu-btn" id="musicMenuBtn" title="歌曲目录" aria-label="歌曲目录">☰</button>'+
            '<div class="xf_music_top">'+
                '<div class="music-disc" id="musicDisc">'+
                    '<img class="music-cover" id="musicCover" alt="封面">'+
                '</div>'+
                '<div class="music-info">'+
                    '<h3 class="music-name" id="musicName">加载中…</h3>'+
                    '<h4 class="music-artist" id="musicArtist">-</h4>'+
                '</div>'+
            '</div>'+
            '<div class="music-controls">'+
                '<button class="music-btn" id="musicPrev" title="上一首" aria-label="上一首">⏮</button>'+
                '<button class="music-btn music-play-btn" id="musicPlay" title="播放/暂停" aria-label="播放/暂停">▶</button>'+
                '<button class="music-btn" id="musicNext" title="下一首" aria-label="下一首">⏭</button>'+
            '</div>'+
            '<div class="music-menu-panel" id="musicMenuPanel" hidden>'+
                '<h5 id="musicMenuTitle">歌曲目录</h5>'+
                '<ul class="music-song-list" id="musicSongList"></ul>'+
            '</div>';
    }

    function cacheElements(){
        el = {
            card: document.getElementById('musicCard'),
            menuBtn: document.getElementById('musicMenuBtn'),
            disc: document.getElementById('musicDisc'),
            cover: document.getElementById('musicCover'),
            name: document.getElementById('musicName'),
            artist: document.getElementById('musicArtist'),
            play: document.getElementById('musicPlay'),
            prev: document.getElementById('musicPrev'),
            next: document.getElementById('musicNext'),
            menuPanel: document.getElementById('musicMenuPanel'),
            menuTitle: document.getElementById('musicMenuTitle'),
            songList: document.getElementById('musicSongList')
        };
    }

    function escapeHtml(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ===== 加载歌榜（缓存即时渲染 + 异步刷新） =====
    function loadChart(){
        try {
            var cached = localStorage.getItem(CACHE_KEY);
            if (cached) applyChart(JSON.parse(cached));
        } catch(e){}
        fetch(API_URL, {cache:'no-cache'})
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res && res.code === 200 && res.data && res.data.songs) {
                    applyChart(res.data);
                    try { localStorage.setItem(CACHE_KEY, JSON.stringify(res.data)); } catch(e){}
                }
            })
            .catch(function(err){
                console.warn('音乐榜读取失败:', err);
                if (!songs.length && el.name) el.name.textContent = '加载失败';
            });
    }

    function applyChart(data){
        songs = (data.songs || []).filter(function(s){ return s && s.picurl; });
        if (!songs.length) {
            if (el.name) el.name.textContent = '暂无歌曲';
            return;
        }
        chartTitle = (data.listName || '歌曲目录') + ' · 共 ' + songs.length + ' 首';
        renderSongList();
        if (el.menuTitle) el.menuTitle.textContent = chartTitle;
        if (currentIndex < 0 || currentIndex >= songs.length) {
            currentIndex = findPlayable(0, 1);
            loadSong(currentIndex, false);
        } else {
            // 歌榜刷新但当前索引仍有效：仅恢复视图，不打断播放
            showSongView(currentIndex);
            if (isPlaying) setPlaying(true);
        }
    }

    function findPlayable(start, dir){
        if (!songs.length) return -1;
        var n = songs.length;
        for (var i = 0; i < n; i++) {
            var idx = ((start + i * dir) % n + n) % n;
            if (songs[idx].url) return idx;
        }
        return -1;
    }

    function renderSongList(){
        if (!el.songList) return;
        var html = '';
        songs.forEach(function(s, i){
            var cls = (i === currentIndex ? 'active' : '');
            html += '<li class="'+cls+'" data-idx="'+i+'">'+
                '<span class="li-name">'+escapeHtml(s.name || '未知歌名')+'</span>'+
                '<span class="li-artist">'+escapeHtml(s.artistsname || '未知歌手')+'</span>'+
            '</li>';
        });
        el.songList.innerHTML = html;
    }

    function updateActiveItem(){
        if (!el.songList) return;
        el.songList.querySelectorAll('li').forEach(function(li){
            var idx = parseInt(li.dataset.idx, 10);
            li.classList.toggle('active', idx === currentIndex);
        });
    }

    // ===== 仅更新视图（不动音频，用于 pjax 后恢复显示） =====
    function showSongView(idx){
        if (idx < 0 || idx >= songs.length) return;
        currentIndex = idx;
        var s = songs[idx];
        if (el.cover) el.cover.src = s.picurl || '';
        if (el.name) el.name.textContent = s.name || '未知歌名';
        if (el.artist) el.artist.textContent = s.artistsname || '未知歌手';
        updateActiveItem();
    }

    // ===== 加载歌曲（更新视图 + 设置音频 + 可选播放） =====
    function loadSong(idx, autoplay){
        if (idx < 0 || idx >= songs.length) return;
        showSongView(idx);
        if (!audio) return;
        var s = songs[idx];
        if (s.url) {
            audio.src = s.url;
            audio.load();
            if (autoplay) playAudio();
        } else {
            audio.removeAttribute('src');
            audio.load();
            setPlaying(false);
            if (autoplay) setTimeout(next, 300);
        }
    }

    function playAudio(){
        if (!audio || !audio.src) return;
        var p = audio.play();
        if (p && p.then) {
            p.then(function(){ setPlaying(true); })
                .catch(function(e){
                    console.warn('播放失败:', e);
                    setPlaying(false);
                });
        }
    }

    function pauseAudio(){
        if (audio) audio.pause();
        setPlaying(false);
    }

    function setPlaying(playing){
        isPlaying = playing;
        if (el.disc) el.disc.classList.toggle('playing', playing);
        if (el.play) el.play.textContent = playing ? '⏸' : '▶';
    }

    function togglePlay(){
        if (!audio) return;
        if (currentIndex < 0 && songs.length) {
            currentIndex = findPlayable(0, 1);
            loadSong(currentIndex, true);
            return;
        }
        if (!audio.src) { next(); return; }
        if (audio.paused) playAudio();
        else pauseAudio();
    }

    function prev(){
        if (!songs.length) return;
        var idx = findPlayable((currentIndex - 1 + songs.length) % songs.length, -1);
        if (idx < 0) return;
        loadSong(idx, true);
    }

    function next(){
        if (!songs.length) return;
        var idx = findPlayable((currentIndex + 1) % songs.length, 1);
        if (idx < 0) return;
        loadSong(idx, true);
    }

    function toggleMenu(){
        if (!el.menuPanel || !el.menuBtn) return;
        var willOpen = el.menuPanel.hidden;
        el.menuPanel.hidden = !willOpen;
        el.menuBtn.classList.toggle('active', willOpen);
    }

    function closeMenu(){
        if (el.menuPanel) el.menuPanel.hidden = true;
        if (el.menuBtn) el.menuBtn.classList.remove('active');
    }

    // ===== 视图事件（每次 init 重建视图后调用，元素是新的，不会累积） =====
    function bindViewEvents(){
        if (el.play) el.play.addEventListener('click', togglePlay);
        if (el.prev) el.prev.addEventListener('click', prev);
        if (el.next) el.next.addEventListener('click', next);
        if (el.menuBtn) el.menuBtn.addEventListener('click', function(e){
            e.stopPropagation();
            toggleMenu();
        });
        if (el.songList) el.songList.addEventListener('click', function(e){
            var li = e.target.closest('li');
            if (!li) return;
            var idx = parseInt(li.dataset.idx, 10);
            if (isNaN(idx) || !songs[idx]) return;
            // url 为空时 loadSong 内部会当作播完自动切下一首
            loadSong(idx, true);
            closeMenu();
        });
    }

    // ===== 初始化（幂等：#musicCard 不存在或已渲染则跳过） =====
    function init(){
        var container = document.getElementById('musicCard');
        if (!container || container.hasChildNodes()) return;

        setupPersistent();      // 确保持久音频存在（只创建一次）
        renderSkeleton(container);
        cacheElements();
        bindViewEvents();

        if (songs.length) {
            // 数据已在内存：恢复视图，不动音频（保持播放连续）
            renderSongList();
            if (el.menuTitle) el.menuTitle.textContent = chartTitle;
            if (currentIndex >= 0 && currentIndex < songs.length) {
                showSongView(currentIndex);
                if (isPlaying) setPlaying(true);  // 恢复旋转/暂停按钮图标
            } else {
                currentIndex = findPlayable(0, 1);
                loadSong(currentIndex, false);
            }
        } else {
            // 首次加载：获取歌榜
            loadChart();
        }
    }

    // ===== 启动 =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ===== pjax 重入：监听 body 直接子节点变化（main 替换时触发） =====
    if (document.body) {
        new MutationObserver(function(){ init(); })
            .observe(document.body, {childList: true, subtree: false});
    }
})();
