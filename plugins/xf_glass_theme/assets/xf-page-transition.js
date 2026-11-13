// xf-page-transition.js —— pjax 局部刷新（DOMParser 方案）
// 保留 header/footer/背景不动，只替换 <main>。
// 用 fetch 拿 HTML 文本 + DOMParser 解析，不执行目标页脚本，不下载重复资源。
// 兼容任何静态 http 部署；file:// 下 fetch 会失败，自动回退整页跳转。
(function(){
    var css='@keyframes xf-pg-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}'
        +'html{background:#0e1117}'
        +'body{animation:xf-pg-in .3s ease both}'
        +'@media (prefers-reduced-motion:reduce){body{animation:none}}'
        +'main.xf-pg-fresh{animation:xf-pg-in .3s ease both}';
    var st=document.createElement('style');
    st.id='xf-page-transition';
    st.textContent=css;
    (document.head||document.documentElement).appendChild(st);

    var swapping=false;

    function isInternal(a){
        var href=a.getAttribute('href');
        if(!href) return false;
        if(href.charAt(0)==='#'||href.indexOf('JavaScript:')===0) return false;
        if(!/\.html(\?|$|#)/.test(href)) return false;
        if(a.target==='_blank'||a.hasAttribute('download')) return false;
        if(a.classList.contains('xf-PicBlackbox')) return false;
        try{ var u=new URL(a.href,location.href); if(u.origin!==location.origin) return false; }catch(e){ return false; }
        return true;
    }

    document.addEventListener('click',function(e){
        if(swapping){ e.preventDefault(); return; }
        var a=e.target.closest&&e.target.closest('a');
        if(!a||!isInternal(a)) return;
        if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey) return;
        if(e.defaultPrevented) return;
        try{ var u=new URL(a.href,location.href);
            if(u.pathname===location.pathname&&u.search===location.search) return;
        }catch(e){ return; }
        e.preventDefault();
        navigate(a.href,true);
    },true);

    window.addEventListener('popstate',function(e){
        var url=(e.state&&e.state.url)||location.href;
        navigate(url,false);
    });

    function navigate(url,push){
        if(swapping) return;
        swapping=true;
        fetch(url,{cache:'no-cache'})
            .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.text(); })
            .then(function(html){
                var doc=new DOMParser().parseFromString(html,'text/html');
                var newMain=doc.querySelector('main');
                var oldMain=document.querySelector('main');
                if(!newMain||!oldMain) throw new Error('main not found');
                // 清除之前 pjax 注入的 <style> 标签（避免样式冲突）
                document.querySelectorAll('head style[data-pjax-style]').forEach(function(s){ s.remove(); });
                // 同步新页 head 里新增的 stylesheet（如详情页专属 style）
                doc.querySelectorAll('head link[rel="stylesheet"]').forEach(function(l){
                    var href=l.getAttribute('href'); if(!href) return;
                    if(!document.querySelector('head link[rel="stylesheet"][href="'+href+'"]')){
                        document.head.appendChild(l.cloneNode(true));
                    }
                });
                // 同步新页 head 里的 <style> 内联样式（如详情页的 .article-back 等）
                doc.querySelectorAll('head style').forEach(function(s){
                    var clone=s.cloneNode(true);
                    clone.setAttribute('data-pjax-style','');
                    document.head.appendChild(clone);
                });
                // 同步新页 title
                document.title=doc.title||document.title;
                // 提取并替换 main（importNode 不携带 script 的可执行性，需后续手动执行）
                var imported=document.importNode(newMain,true);
                imported.classList.add('xf-pg-fresh');
                oldMain.replaceWith(imported);
                // 右侧栏用缓存即时填充
                if(window.__xfCfg){
                    try{ renderProfile(window.__xfCfg); }catch(_){}
                    try{ renderStats(window.__xfCfg); }catch(_){}
                }
                // 先更新 URL，确保页面脚本里 location.search 已是新页的（详情页依赖 ?file=xxx）
                if(push) history.pushState({url:url},'',url);
                // 执行目标页专属脚本：按原顺序串行处理
                // 公共脚本（xf-page-transition.js / xf-common.js）已加载，跳过避免重复绑定
                // 页面专属 src 脚本（如 marked.js）：已加载则跳过，否则等待 onload 后继续
                // 内联 script：在主文档作用域执行（页面专属初始化）
                var PUBLIC_SCRIPTS=['xf-page-transition.js','xf-common.js'];
                function isPublic(src){ return src && PUBLIC_SCRIPTS.some(function(p){ return src.indexOf(p)>-1; }); }
                var scripts=Array.prototype.slice.call(doc.querySelectorAll('script')).filter(function(s){
                    var src=s.getAttribute('src');
                    if(isPublic(src)) return false; // 公共脚本已执行，跳过
                    return true;
                });
                function runNext(i){
                    if(i>=scripts.length){ swapping=false; window.scrollTo(0,0); return; }
                    var oldScript=scripts[i];
                    var src=oldScript.getAttribute('src');
                    if(src){
                        if(document.querySelector('script[src="'+src+'"]')){ runNext(i+1); return; }
                        var s=document.createElement('script');
                        if(oldScript.type) s.type=oldScript.type;
                        s.src=src;
                        s.onload=function(){ runNext(i+1); };
                        s.onerror=function(){ console.warn('pjax script load fail:',src); runNext(i+1); };
                        document.body.appendChild(s);
                    }else{
                        try{ (new Function(oldScript.textContent))(); }catch(e){ console.warn('pjax inline script error:',e); }
                        runNext(i+1);
                    }
                }
                runNext(0);
            })
            .catch(function(err){
                console.warn('pjax fail, fallback:',err);
                swapping=false;
                location.href=url;
            });
    }

    // 初始建立 history state
    if(!history.state||!history.state.url){
        history.replaceState({url:location.href},'',location.href);
    }
})();
