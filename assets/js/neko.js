/* global jQuery, SGN_CFG */
(function ($) {
    'use strict';

    // 1. 加载配置参数 (从 PHP 注入的变量获取)
    const cfg = (window.SGN_CFG || {});
    const f = cfg.features || {};
    const txt = cfg.texts || {};

    // 2. 定义宠物全局状态对象
    const PET = {
        $root: $('#sgn-pet-root'),        // 宠物容器
        $msg: $('#sgn-pet-msg'),          // 气泡元素
        $flipper: $('#sgn-pet-flipper'),  // 翻转容器 (用于左右转身)
        $body: $('#sgn-pet-body'),        // 身体元素 (用于动作动画)
        $toy: $('#sgn-toy-item'),         // 玩具/食物元素
        $effect: $('#sgn-effect-layer'),  // 特效层 (抓痕)

        w: 64, h: 64,                     // 宠物尺寸

        // 物理参数
        ground: 0,                        // 地面高度 (bottom值)
        floatHeight: 50,                  // 幽灵的悬浮高度
        isGhost: (cfg.model === 'ghost'), // 是否为幽灵模式
        posX: 20, posY: 0,                // 当前位置
        velY: 0,                          // 垂直速度 (用于重力计算)
        gravity: 1.5,                     // 重力加速度
        bounce: 0.6,                      // 弹跳系数

        // 状态机
        state: 'IDLE',                    // 当前状态: IDLE, WALK, SLEEP, DRAG, FALL, CHASE, ACT, ATTACK
        timer: null,                      // AI 循环定时器
        msgTimer: null,                   // 气泡消失定时器
        lastScrollTop: 0,                 // 上次滚动位置

        // 交互记录
        lastInputTime: Date.now(),        // 最后互动时间 (用于判断睡眠)
        mouseShakeCount: 0,               // 鼠标晃动计数 (用于唤醒)
        lastMouseX: 0,                    // 上次鼠标X坐标
        facing: 'left',                   // 当前朝向
        lastAttackTime: 0                 // 上次攻击时间 (冷却用)
    };

    // 如果没找到元素，直接退出，防止报错
    if (!PET.$root.length) return;

    // --- 工具函数 (Helpers) ---
    // 限制数值范围
    function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }
    // 获取当前时间戳
    function now() { return Date.now(); }
    // 记录用户活动 (防止入睡)
    function recordInput() { PET.lastInputTime = now(); }

    // 说话气泡 (自动判断屏幕边缘，防止气泡溢出)
    function say(text, duration) {
        if (!text) return; // 文本为空不显示
        duration = duration || 3000;
        PET.$msg.html(String(text)).removeClass('sgn-bubble-left sgn-bubble-right sgn-bubble-center');
        
        const rect = PET.$root[0].getBoundingClientRect();
        const winW = $(window).width();
        
        // 如果靠左 < 140px，气泡向右弹出
        if (rect.left < 140) PET.$msg.addClass('sgn-bubble-left');
        // 如果靠右 > 屏幕宽-140px，气泡向左弹出
        else if (rect.left > winW - 140) PET.$msg.addClass('sgn-bubble-right');
        // 否则居中
        else PET.$msg.addClass('sgn-bubble-center');
        
        PET.$msg.addClass('show');
        clearTimeout(PET.msgTimer);
        PET.msgTimer = setTimeout(() => PET.$msg.removeClass('show'), duration);
    }

    // 说本地语录
    function sayLocal() { const arr = cfg.sentences || ['...']; say(arr[Math.floor(Math.random() * arr.length)]); }

    // 说网络语录 (API)
    function sayRandom() {
        const url = (cfg.apiUrl || '').trim();
        if (url && Math.random() < 0.85) { // 85% 概率请求API
            $.ajax({ url, method: 'GET', timeout: 2500, cache: false })
                .done((data) => {
                    let t = '';
                    // 尝试解析一言格式
                    try { if (typeof data === 'string' && data.includes('{')) data = JSON.parse(data); t = (data.hitokoto || data.text || data.content || '').toString().trim(); } catch (e) { }
                    t ? say(t) : sayLocal();
                }).fail(() => sayLocal());
        } else sayLocal();
    }

    // 设置朝向
    function setFacing(dir) {
        const d = (dir === 'right') ? 'right' : 'left';
        let scale = (d === 'right') ? -1 : 1;
        if (cfg.model === 'pigeon') scale *= -1; // 鸽子素材是反向的，需要特殊处理
        PET.$flipper.css('transform', `scaleX(${scale})`);
        PET.facing = d;
    }

    // 面向目标X坐标
    function faceToX(targetX) { setFacing(targetX > (parseFloat(PET.$root.css('left')) || 0) ? 'right' : 'left'); }

    // 停止所有动作和动画
    function stopAll() {
        PET.$root.stop(true); PET.$toy.stop(true); PET.$body.stop(true);
        PET.$root.removeClass('moving sgn-falling'); PET.$body.css('animation', '');
        clearTimeout(PET.timer);
    }

    // --- 核心逻辑 (Core Logic) ---

    // 判断是否应该睡觉
    function shouldSleep() {
        if (!f.enableSleep || PET.state === 'DRAG' || PET.state === 'FALL') return false;
        const h = new Date().getHours();
        
        // 读取后台配置的睡眠时间
        const start = cfg.sleepStart ?? 23;
        const end = cfg.sleepEnd ?? 6;
        let isSleepTime = false;
        
        if (start > end) { // 跨天，例如 23点 到 6点
            isSleepTime = (h >= start || h < end);
        } else { // 当天，例如 1点 到 5点
            isSleepTime = (h >= start && h < end);
        }

        // 是睡眠时间 或 闲置时间超标
        return isSleepTime || (now() - PET.lastInputTime > (cfg.idleSleepMs || 300000));
    }

    // 进入睡眠状态
    function enterSleep() {
        if (PET.state === 'SLEEP') return;
        stopAll(); PET.state = 'SLEEP'; PET.$root.addClass('sleeping');
        // 修正位置：幽灵浮空，其他落地
        const b = PET.isGhost ? PET.floatHeight : 0; PET.posY = b; PET.$root.css({ bottom: b, top: 'auto' });
    }

    // 唤醒
    function wakeUp(rude) {
        PET.state = 'IDLE'; PET.$root.removeClass('sleeping'); recordInput();
        if (rude) { say('别摇了！', 2000); PET.$body.css('animation', 'sgn-shake 0.5s'); } // 暴力唤醒
        else say('嗯...醒了...', 2000);
        setTimeout(() => PET.$body.css('animation', ''), 500);
        scheduleNext(1200, 2200); // 稍后进入 AI 循环
    }

    // --- 拖拽与点击逻辑 (Drag & Click) ---
    let drag = { x: 0, y: 0, t: 0, ox: 0, oy: 0, active: false };

    // 鼠标/手指按下
    function onDown(e) {
        if (PET.state === 'SLEEP') { wakeUp(true); return; } // 睡眠时点击则唤醒
        recordInput(); drag.active = true;
        
        const c = e.touches ? e.touches[0] : e;
        const r = PET.$root[0].getBoundingClientRect();
        
        // 记录初始位置和偏移量
        drag.x = c.clientX; drag.y = c.clientY; 
        drag.t = now(); 
        drag.ox = c.clientX - r.left; 
        drag.oy = c.clientY - r.top;
        
        // 注意：这里不 preventDefault，否则移动端无法触发 click 事件进行喂食
    }

    // 鼠标/手指移动
    function onMove(e) {
        recordInput(); if (!drag.active) return;
        const c = e.touches ? e.touches[0] : e;

        // 如果已经在拖拽模式
        if (PET.state === 'DRAG') {
            PET.$root.css({ left: c.clientX - drag.ox, top: c.clientY - drag.oy, bottom: 'auto' });
            if (e.cancelable && e.touches) e.preventDefault(); // 阻止页面滚动
            return;
        }

        // 计算移动距离，超过 5px 才判定为拖拽
        if (Math.sqrt(Math.pow(c.clientX - drag.x, 2) + Math.pow(c.clientY - drag.y, 2)) > 5 && f.enableDrag) {
            stopAll(); PET.state = 'DRAG'; PET.$root.addClass('dragging');
            say(PET.isGhost ? '飞高高~' : '放开我！', 9000);
        }
    }

    // 鼠标/手指松开
    function onUp() {
        if (!drag.active) return; drag.active = false;

        if (PET.state === 'DRAG') {
            // 结束拖拽，开始下落
            PET.state = 'FALL'; PET.$root.removeClass('dragging');
            const r = PET.$root[0].getBoundingClientRect();
            // 限制在屏幕内
            PET.posX = clamp(r.left, 0, $(window).width() - PET.w);
            PET.posY = clamp($(window).height() - r.bottom, 0, $(window).height() - PET.h);
            PET.$root.css({ top: 'auto', bottom: PET.posY, left: PET.posX });
            PET.velY = 0; requestAnimationFrame(physicsLoop);
        } else {
            // 移动距离短，视为点击
            handleClick();
        }
    }

    // 点击事件处理
    function handleClick() {
        if (PET.state !== 'IDLE') return;
        
        // 喂食判定
        if (f.enableToy && Math.random() < (cfg.toyChance || 0.65)) { dropToy(); return; }
        
        // 攻击判定
        if (f.enableAttack && now() - PET.lastAttackTime > 30000 && Math.random() < (cfg.attackChance || 0.05)) { triggerAttack(); return; }
        
        // 默认动作：跳跃 + 说话
        sayRandom();
        PET.$body.addClass('sgn-act-jump'); setTimeout(() => PET.$body.removeClass('sgn-act-jump'), 600);
    }

    // 绑定事件
    PET.$root.on('mousedown touchstart', onDown);
    $(document).on('mousemove touchmove', onMove);
    $(document).on('mouseup touchend touchcancel', onUp);

    // --- 物理引擎循环 (Physics Loop) ---
    function physicsLoop() {
        if (PET.state !== 'FALL') return;
        const tY = PET.isGhost ? PET.floatHeight : PET.ground;

        if (PET.isGhost) {
            // 幽灵：平滑飘落
            const d = tY - PET.posY; PET.posY += d * 0.06;
            if (Math.abs(d) < 0.8) { PET.posY = tY; PET.$root.css('bottom', PET.posY); PET.state = 'IDLE'; scheduleNext(); return; }
        } else {
            // 实体：重力下落 + 弹跳
            PET.velY -= PET.gravity; PET.posY += PET.velY;
            if (PET.posY <= tY) {
                PET.posY = tY; PET.velY *= -PET.bounce; // 反弹
                if (Math.abs(PET.velY) < 2) { // 速度足够小停止
                    PET.state = 'IDLE'; PET.$root.css('bottom', PET.posY);
                    say('着陆成功。', 1500); scheduleNext(); return;
                }
            }
        }
        PET.$root.css('bottom', PET.posY); requestAnimationFrame(physicsLoop);
    }

    // --- 复杂交互 (Interactions) ---

    // 移动到指定 X 坐标
    function walkTo(targetX, speed, cb) {
        if (PET.state !== 'IDLE') return;
        PET.state = 'WALK';
        const winW = $(window).width();
        const x = clamp(targetX, 0, winW - PET.w);
        
        if (PET.isGhost) PET.$root.addClass('moving');
        faceToX(x);
        
        // 非幽灵有走路动画
        if (!PET.isGhost) PET.$body.css('animation', 'sgn-walk-bounce 0.2s infinite');
        
        // 计算移动耗时
        const dur = Math.max(300, Math.abs(x - (parseFloat(PET.$root.css('left')) || 0)) * (PET.isGhost ? 8 : 5) / (speed || 1));
        
        PET.$root.animate({ left: x }, dur, 'linear', () => {
            PET.$body.css('animation', ''); PET.$root.removeClass('moving'); PET.state = 'IDLE';
            if (cb) cb();
        });
    }

    // 掉落玩具/食物
    function dropToy() {
        if (!f.enableToy || PET.state !== 'IDLE') return;
        PET.state = 'CHASE'; clearTimeout(PET.timer);
        
        const label = (cfg.labels && cfg.labels.toy && cfg.labels.toy[cfg.model]) || '好吃的';
        say(label + '！是我的！', 1500);

        const winW = $(window).width();
        // 修正：使用 innerHeight 适应安卓地址栏
        const winH = window.innerHeight; 
        const tx = clamp(Math.random() * (winW - 100), 0, winW - 40);

        // 玩具掉落动画
        PET.$toy.css({ left: tx, top: -50, display: 'block' }).animate({ top: winH - 40 }, 600, 'swing', () => {
            PET.state = 'IDLE';

            // 计算宠物停止位置，确保头部对准食物
            const currentX = parseFloat(PET.$root.css('left')) || 0;
            let stopX = tx;
            if (tx > currentX) {
                // 向右跑，停在食物左边，让头部覆盖食物
                stopX = tx - 30; 
            } else {
                // 向左跑，停在食物右边
                stopX = tx + 10;
            }

            walkTo(stopX, PET.isGhost ? 1.5 : 2.5, () => {
                PET.state = 'CHASE';
                PET.$body.addClass('sgn-act-eat'); // 进食动画
                setTimeout(() => {
                    PET.$body.removeClass('sgn-act-eat');
                    PET.$toy.fadeOut(200);
                    say(PET.isGhost ? '灵魂得到了满足~' : '好吃！还有吗？', 2000);
                    PET.state = 'IDLE';
                    scheduleNext(2500, 3000);
                }, 1200);
            });
        });
    }

    // 攻击屏幕特效
    function triggerAttack() {
        if (!f.enableAttack || PET.state !== 'IDLE') return;
        PET.state = 'ATTACK'; PET.lastAttackTime = now();
        say('我要闹了！', 1000);
        setTimeout(() => {
            $('body').addClass('sgn-glitch-active'); // 屏幕故障特效
            PET.$effect.show().empty();
            // 生成抓痕
            for (let i = 0; i < 5; i++) {
                const m = $('<div class="sgn-claw-mark"></div>');
                m.css({ left: Math.random() * (window.innerWidth - 200), top: Math.random() * (window.innerHeight - 100) });
                PET.$effect.append(m);
            }
            setTimeout(() => {
                $('body').removeClass('sgn-glitch-active');
                PET.$effect.hide().empty();
                PET.state = 'IDLE';
                say('舒服了。', 1500);
                scheduleNext();
            }, 1200);
        }, 800);
    }

    // --- AI 行为循环 (AI Loop) ---
    function aiLoop() {
        if (PET.state !== 'IDLE') return;
        if (shouldSleep()) { enterSleep(); return; }
        const r = Math.random();
        
        // 随机动作
        if (Math.random() < (cfg.actionChance || 0)) {
            const acts = ['sit', 'spin', 'jump', 'stretch'];
            const act = acts[Math.floor(Math.random() * acts.length)];
            PET.$body.addClass('sgn-act-' + act);
            setTimeout(() => { PET.$body.removeClass('sgn-act-' + act); scheduleNext(); }, 1000);
            return;
        }
        
        // 随机移动
        if (r < 0.4) {
            walkTo(Math.random() * ($(window).width() - 100), 1, () => {
                if (Math.random() < 0.3) sayRandom();
                scheduleNext();
            });
        } else {
            // 随机说话
            if (Math.random() < (cfg.talkChance || 0.45)) sayRandom();
            scheduleNext();
        }
    }
    
    // 安排下一次 AI 动作
    function scheduleNext(min, jitter) { clearTimeout(PET.timer); PET.timer = setTimeout(aiLoop, (min || 2500) + Math.random() * (jitter || 3500)); }

    // --- 事件监听 (Listeners) ---

    // 1. 离屏标题互动
    if (f.enableVisibility) {
        let originTitle = document.title;
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // 只有当当前标题不是插件设置的标题时，才保存原始标题
                if (document.title !== (txt.titleLost || '🥺 人呢？去哪了？') && document.title !== (txt.titleBack || '🎉 欢迎回来！')) {
                    originTitle = document.title;
                }
                document.title = txt.titleLost || '🥺 人呢？去哪了？';
            } else {
                document.title = txt.titleBack || '🎉 欢迎回来！';
                setTimeout(() => { document.title = originTitle; }, 2000);
                if (PET.state !== 'SLEEP' && txt.msgBack) say(txt.msgBack, 3000);
            }
        });
    }

    // 2. 滚动失重
    if (f.enableScroll) {
        let stT;
        $(window).on('scroll', () => {
            recordInput(); if (stT) return; stT = setTimeout(() => { stT = null }, 50); // 节流
            if (PET.state !== 'IDLE' || PET.isGhost) return;
            const t = $(window).scrollTop(), d = t - PET.lastScrollTop;
            
            // 滚动速度超过阈值
            if (Math.abs(d) > (cfg.scrollThreshold || 60)) {
                stopAll(); PET.$root.addClass('sgn-falling');
                // 根据滚动方向调整位置
                PET.$root.css('bottom', d > 0 ? '-20px' : '50px');
                say(d > 0 ? '啊啊啊~' : '飞起来了！', 800);
                setTimeout(() => { PET.$root.removeClass('sgn-falling'); PET.$root.css('bottom', 0); }, 600);
            }
            PET.lastScrollTop = t;
        });
    }

    // 3. 鼠标伴读
    if (f.enableReading) {
        $(document).on('mouseenter', 'a,p', function () {
            if (PET.state !== 'IDLE') return;
            if (Math.random() > (cfg.readingChance || 0.3)) return;
            const t = $(this).text().trim().substring(0, 10);
            if (t.length > 1) say('在看“' + t + '”吗？');
        });
    }

    // 4. 睡眠中鼠标晃动唤醒
    $(document).on('mousemove', e => {
        if (PET.state === 'SLEEP') {
            if (Math.abs(e.clientX - PET.lastMouseX) > 50) { PET.mouseShakeCount++; if (PET.mouseShakeCount > 10) wakeUp(true); }
            PET.lastMouseX = e.clientX;
        }
    });

    // --- 初始化 ---
    $(function () {
        if (PET.isGhost) { PET.posY = PET.floatHeight; PET.$root.css('bottom', PET.posY); }
        setFacing('left');
        
        // 自动问候
        const h = new Date().getHours();
        if (h >= 6 && h < 11 && txt.morning) say(txt.morning, 4000);
        else if (h >= 11 && h < 14 && txt.noon) say(txt.noon, 4000);
        else if (h >= 20 && h < 24 && txt.night) say(txt.night, 4000);
        
        // 启动 AI
        scheduleNext(1000, 2000);
    });
})(jQuery);
