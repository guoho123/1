// xf-common.js —— 全站公共脚本（抽离自各 HTML 内联）
// 包含：时钟 / ttt.json 渲染 / 手机菜单 / stat 翻转 / xf-PicBlackbox 图片弹窗 / 配置缓存
// 各页面只需在 body 末引用一次；pjax 切换不会重复执行（引擎用 DOMParser，不执行目标页脚本）
(function(){
    // ===== pjax ready 信号（iframe 加载场景遗留兼容，DOMParser 方案下也保留以便页面主动通知） =====
    function xfReady(){
        if(window.parent&&window.parent!==window) parent.postMessage({type:'xf-pjax-ready'},'*');
    }
    window.xfReady=xfReady;

    // ===== 时钟 =====
    function rotateHand(target,val){ if(target) target.style.transform="rotate("+val+"deg)"; }
    function xfClock(){
        var xfHours=document.querySelector(".hours");
        var xfMinutes=document.querySelector(".minutes");
        var xfSeconds=document.querySelector(".seconds");
        var today=new Date();
        var h=(today.getHours()%12)+today.getMinutes()/59;
        var m=today.getMinutes();
        var s=today.getSeconds();
        h*=30; m*=6; s*=6;
        rotateHand(xfHours,h);
        rotateHand(xfMinutes,m);
        rotateHand(xfSeconds,s);
        setTimeout(xfClock,500);
    }
    function xfppp(s){ return s<10?'0'+s:s; }
    function xfDigitalTime(){
        var t=new Date();
        var weeks=['星期天','星期一','星期二','星期三','星期四','星期五','星期六'];
        var t1=document.querySelector(".xf_time_1");
        var t2=document.querySelector(".xf_time_2");
        var t3=document.querySelector(".xf_time_3");
        if(t1) t1.innerText=xfppp(t.getHours())+':'+xfppp(t.getMinutes());
        if(t2) t2.innerText=t.getFullYear()+'年'+xfppp(t.getMonth()+1)+'月'+xfppp(t.getDate())+'日';
        if(t3) t3.innerText=weeks[t.getDay()];
    }
    window.xfClock=xfClock;
    window.xfDigitalTime=xfDigitalTime;

    // ===== ttt.json 渲染 =====
    function daysBetween(fromStr){
        var from=new Date(fromStr);
        var now=new Date();
        var ms=now-from;
        return Math.floor(ms/(1000*60*60*24));
    }
    function renderProfile(cfg){
        var area=document.getElementById('profileArea');
        if(!area || !cfg) return;
        var p=cfg.profile || {};
        var socials=cfg.socials || [];
        var socialHtml='';
        socials.forEach(function(s){
            if(s.type==='pic'){
                socialHtml+='<a class="social-btn xf-PicBlackbox" data-pic="'+(s.pic||'')+'" data-text="'+(s.popup||'')+'">'+(s.text||'🔗')+'</a>';
            }else{
                socialHtml+='<a class="social-btn" href="'+(s.href||'#')+'">'+(s.text||'🔗')+'</a>';
            }
        });
        // 我的其他网站 HTML（卡片样式，外部跳转新窗口，符合 pjax 外链规则）
        var sites=cfg.sites || [];
        var sitesHtml=buildSitesHtml(sites);
        // 渲染位置：若页面存在独立 #sitesArea（音乐播放器后），则 sites 渲染到那里；
        // 否则回退到 #profileArea 末尾（保持 index.html 原行为不变）
        var sitesArea=document.getElementById('sitesArea');
        var sitesInProfile=!sitesArea;
        var descHtml=String(p.desc||'')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\r\n|\r|\n/g,'<br>');
        area.innerHTML='<div class="profile-head"><img class="avatar" src="'+(p.avatar||'')+'" alt="头像"><div class="profile-name"><h2>'+(p.name||'')+'</h2></div></div><div class="glass desc-card"><p>'+descHtml+'</p></div><div class="social-row">'+socialHtml+'</div>'+(sitesInProfile?sitesHtml:'');
        // 独立 sitesArea 渲染
        if(sitesArea){
            sitesArea.innerHTML=sitesHtml||'';
            if(sitesHtml) loadSiteCovers(sitesArea);
        }else if(sitesHtml){
            loadSiteCovers(area);
        }
    }

    // ===== 构建 sites HTML =====
    function buildSitesHtml(sites){
        if(!Array.isArray(sites) || !sites.length) return '';
        var listHtml='';
        sites.forEach(function(t){
            var txt=String(t.text==null?'':t.text)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;');
            var href=String(t.href==null?'#':t.href)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;');
            var cover=String(t.cover==null?'':t.cover)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;');
            var coverInner=cover
                ? '<div class="skeleton-shimmer"></div>'
                : '<div class="site-ph">无图</div>';
            listHtml+='<a class="site-card" href="'+href+'" target="_blank" rel="noopener" data-cover="'+cover+'" data-name="'+txt+'">'+
                '<div class="site-cover">'+coverInner+'</div>'+
                '<div class="site-name">'+txt+'</div>'+
            '</a>';
        });
        return '<div class="sites-row"><div class="sites-title">我的其他网站</div><div class="sites-list">'+listHtml+'</div></div>';
    }

    // ===== sites 封面图懒加载 =====
    function loadSiteCovers(scope){
        var imgs=(scope||document).querySelectorAll('.site-card[data-cover]');
        if(!imgs.length) return;
        function fill(el){
            var src=el.getAttribute('data-cover');
            if(!src) return;
            var img=new Image();
            img.alt=el.getAttribute('data-name')||'';
            img.onload=function(){
                var cover=el.querySelector('.site-cover');
                if(cover) cover.innerHTML='';
                if(cover) cover.appendChild(img);
            };
            img.onerror=function(){
                var cover=el.querySelector('.site-cover');
                if(cover) cover.innerHTML='<div class="site-ph">加载失败</div>';
            };
            img.src=src;
        }
        if('IntersectionObserver' in window){
            var io=new IntersectionObserver(function(entries){
                entries.forEach(function(entry){
                    if(entry.isIntersecting){
                        fill(entry.target);
                        io.unobserve(entry.target);
                    }
                });
            },{rootMargin:'100px'});
            imgs.forEach(function(el){ io.observe(el); });
        }else{
            imgs.forEach(function(el){ fill(el); });
        }
    }
    function renderStats(cfg){
        var grid=document.getElementById('statGrid');
        if(!grid || !cfg) return;
        var st=cfg.stats || {};
        var startDate=st.startDate || '2024-03-01';
        var expStart=st.experienceStart || startDate;
        var projCount=st.projectCount != null ? st.projectCount : 0;
        var startFmt=startDate.replace(/-/g,'/');
        var expFmt=expStart.replace(/-/g,'/');
        var days=daysBetween(startDate);
        var years=(days/365).toFixed(1);
        var expDays=daysBetween(expStart);
        var expYears=(expDays/365).toFixed(1);
        grid.innerHTML='<div class="stat-item" data-start="'+startDate+'"><button class="stat-flip-btn" aria-label="翻转" title="查看开始时间">↵</button><div class="stat-card-inner"><div class="stat-card-face stat-front"><div class="stat-label">入坑</div><div class="stat-num">'+years+'年</div></div><div class="stat-card-face stat-back"><div class="stat-label">开始日期</div><div class="stat-start-date">'+startFmt+'</div></div></div></div><div class="stat-item" data-start="'+expStart+'"><button class="stat-flip-btn" aria-label="翻转" title="查看开始时间">↵</button><div class="stat-card-inner"><div class="stat-card-face stat-front"><div class="stat-label">经验积累</div><div class="stat-num">'+expYears+'年</div></div><div class="stat-card-face stat-back"><div class="stat-label">开始日期</div><div class="stat-start-date">'+expFmt+'</div></div></div></div><div class="stat-item"><div class="stat-card-inner"><div class="stat-card-face stat-front"><div class="stat-label">项目数量</div><div class="stat-num">'+projCount+'</div><div class="stat-label">个</div></div></div></div>';
    }
    window.renderProfile=renderProfile;
    window.renderStats=renderStats;

    // ===== 手机端汉堡菜单 =====
    function initNavToggle(){
        var navToggle=document.getElementById('navToggle');
        var navMenu=document.getElementById('navMenu');
        if(!navToggle || !navMenu) return;
        navToggle.addEventListener('click',function(){
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('open');
        });
        navMenu.querySelectorAll('a').forEach(function(a){
            a.addEventListener('click',function(){
                navToggle.classList.remove('active');
                navMenu.classList.remove('open');
            });
        });
    }

    // ===== stat-item 翻转（事件委托） =====
    document.addEventListener('click',function(e){
        var btn=e.target.closest('.stat-flip-btn');
        if(btn){
            e.stopPropagation();
            var item=btn.closest('.stat-item');
            if(item) item.classList.toggle('flipped');
        }
    });

    // ===== xf-PicBlackbox 图片弹窗（事件委托） =====
    document.addEventListener('click',function(e){
        var dom=e.target.closest('.xf-PicBlackbox');
        if(!dom) return;
        var dataPopup=dom.getAttribute('data-popup');
        if(!dom.getAttribute('data-pic') && dataPopup!==null) return;
        e.preventDefault();
        if(document.querySelector('.xf-mainBox')) return;
        var picLink=dom.getAttribute('data-pic')||dom.src||'';
        var zdyText=dom.getAttribute('data-text');
        if(!picLink){alert('未设置data-pic属性');return;}
        var mainBox=document.createElement('div');mainBox.className='xf-mainBox';
        var inside=document.createElement('div');inside.className='xf-fadeOutDiv';
        var mask=document.createElement('div');mask.className='xf-masking';
        var img=document.createElement('img');img.className='xf-fadeOutPic';
        var txt=document.createElement('p');txt.className='xf-fadeOutText';
        var del=document.createElement('i');del.className='xf-delDom';del.textContent='×';
        img.src=picLink;
        if(zdyText!==null){txt.textContent=zdyText;img.alt=zdyText;inside.append(img,txt,del);}
        else{inside.append(img,del);}
        mainBox.append(inside,mask);
        document.body.appendChild(mainBox);
        var pic=mainBox.querySelector('.xf-fadeOutPic');
        var fadeDiv=mainBox.querySelector('.xf-fadeOutDiv');
        var tmp=new Image();
        tmp.onload=function(){
            if(tmp.width>tmp.height){pic.style.width='100%';fadeDiv.classList.add('Div-w');}
            else if(tmp.width<tmp.height){pic.style.height='100%';fadeDiv.classList.add('Div-h');}
            else{pic.style.width='100%';pic.style.height='100%';fadeDiv.classList.add('Div-center');}
        };
        tmp.src=picLink;
        mainBox.classList.add('xf-fadeIn');
        var txtEl=mainBox.querySelector('.xf-fadeOutText');
        if(txtEl){
            txtEl.addEventListener('click',function(){
                var t=txtEl.textContent;
                if(navigator.clipboard){navigator.clipboard.writeText(t).then(function(){alert('复制成功');}).catch(function(){alert('复制失败');});}
                else{alert(t);}
            });
        }
        function xfClose(){mainBox.classList.remove('xf-fadeIn');mainBox.classList.add('xf-fadeOut');setTimeout(function(){mainBox.remove();},350);}
        mask.addEventListener('click',xfClose);
        del.addEventListener('click',xfClose);
    });

    // ===== ttt.json 加载（localStorage 缓存） =====
    var CFG_KEY='xf_cfg_v1';
    function loadCfg(){
        // 先用缓存即时渲染
        try{
            var cached=localStorage.getItem(CFG_KEY);
            if(cached){
                var cfg=JSON.parse(cached);
                window.__xfCfg=cfg;
                renderProfile(cfg);
                renderStats(cfg);
            }
        }catch(e){}
        // 再异步刷新最新值
        fetch('ttt.json',{cache:'no-cache'})
            .then(function(r){ return r.json(); })
            .then(function(cfg){
                window.__xfCfg=cfg;
                renderProfile(cfg);
                renderStats(cfg);
                try{ localStorage.setItem(CFG_KEY,JSON.stringify(cfg)); }catch(e){}
            })
            .catch(function(err){ console.warn('ttt.json 读取失败:',err); });
    }
    window.__loadCfg=loadCfg;


    
    // ===== 页面初始化总入口 =====
    function initPage(){
        xfClock();
        xfDigitalTime();
        setInterval(xfDigitalTime,1000);
        initNavToggle();
        loadCfg();
    }


    
    // DOMContentLoaded 已过（脚本在 body 末）则立即执行，否则等待
    if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',initPage);
    }else{
        initPage();
    }
})();
