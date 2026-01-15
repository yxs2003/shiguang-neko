/* global jQuery, SGN_CFG */
(function ($) {
    'use strict';
    const cfg = (window.SGN_CFG || {});
    const f = cfg.features || {};
    const txt = cfg.texts || {};

    const PET = {
        $root: $('#sgn-pet-root'), $msg: $('#sgn-pet-msg'), $flipper: $('#sgn-pet-flipper'),
        $body: $('#sgn-pet-body'), $toy: $('#sgn-toy-item'), $effect: $('#sgn-effect-layer'),
        w: 64, h: 64,
        ground: 0, floatHeight: 50, isGhost: (cfg.model === 'ghost'),
        posX: 20, posY: 0, velY: 0, gravity: 1.5, bounce: 0.6,
        state: 'IDLE', timer: null, msgTimer: null, lastScrollTop: 0,
        lastInputTime: Date.now(), mouseShakeCount: 0, lastMouseX: 0, facing: 'left',
        lastAttackTime: 0
    };

    if (!PET.$root.length) return;

    // --- Helpers ---
    function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }
    function now() { return Date.now(); }
    function recordInput() { PET.lastInputTime = now(); }

    function say(text, duration) {
        if (!text) return; // 如果文本为空，直接不说话
        duration = duration || 3000;
        PET.$msg.html(String(text)).removeClass('sgn-bubble-left sgn-bubble-right sgn-bubble-center');
        const rect = PET.$root[0].getBoundingClientRect();
        const winW = $(window).width();
        if (rect.left < 140) PET.$msg.addClass('sgn-bubble-left');
        else if (rect.left > winW - 140) PET.$msg.addClass('sgn-bubble-right');
        else PET.$msg.addClass('sgn-bubble-center');
        PET.$msg.addClass('show');
        clearTimeout(PET.msgTimer);
        PET.msgTimer = setTimeout(() => PET.$msg.removeClass('show'), duration);
    }
    function sayLocal() { const arr = cfg.sentences || ['...']; say(arr[Math.floor(Math.random() * arr.length)]); }
    function sayRandom() {
        const url = (cfg.apiUrl || '').trim();
        if (url && Math.random() < 0.85) {
            $.ajax({ url, method: 'GET', timeout: 2500, cache: false })
                .done((data) => {
                    let t = '';
                    try { if (typeof data === 'string' && data.includes('{')) data = JSON.parse(data); t = (data.hitokoto || data.text || data.content || '').toString().trim(); } catch (e) { }
                    t ? say(t) : sayLocal();
                }).fail(() => sayLocal());
        } else sayLocal();
    }

    function setFacing(dir) {
        const d = (dir === 'right') ? 'right' : 'left';
        let scale = (d === 'right') ? -1 : 1;
        if (cfg.model === 'pigeon') scale *= -1;
        PET.$flipper.css('transform', `scaleX(${scale})`);
        PET.facing = d;
    }
    function faceToX(targetX) { setFacing(targetX > (parseFloat(PET.$root.css('left')) || 0) ? 'right' : 'left'); }
    function stopAll() {
        PET.$root.stop(true); PET.$toy.stop(true); PET.$body.stop(true);
        PET.$root.removeClass('moving sgn-falling'); PET.$body.css('animation', '');
        clearTimeout(PET.timer);
    }

    // --- Core Logic ---
    function shouldSleep() {
        if (!f.enableSleep || PET.state === 'DRAG' || PET.state === 'FALL') return false;
        const h = new Date().getHours();

        // 使用配置的睡眠时间
        const start = cfg.sleepStart ?? 23;
        const end = cfg.sleepEnd ?? 6;
        let isSleepTime = false;

        if (start > end) { // 跨天，例如 23点到 6点
            isSleepTime = (h >= start || h < end);
        } else { // 当天，例如 1点到 5点
            isSleepTime = (h >= start && h < end);
        }

        return isSleepTime || (now() - PET.lastInputTime > (cfg.idleSleepMs || 300000));
    }

    function enterSleep() {
        if (PET.state === 'SLEEP') return;
        stopAll(); PET.state = 'SLEEP'; PET.$root.addClass('sleeping');
        const b = PET.isGhost ? PET.floatHeight : 0; PET.posY = b; PET.$root.css({ bottom: b, top: 'auto' });
    }
    function wakeUp(rude) {
        PET.state = 'IDLE'; PET.$root.removeClass('sleeping'); recordInput();
        if (rude) { say('别摇了！', 2000); PET.$body.css('animation', 'sgn-shake 0.5s'); }
        else say('嗯...醒了...', 2000);
        setTimeout(() => PET.$body.css('animation', ''), 500);
        scheduleNext(1200, 2200);
    }

    // --- Drag & Click ---
    let drag = { x: 0, y: 0, t: 0, ox: 0, oy: 0, active: false };
    function onDown(e) {
        if (PET.state === 'SLEEP') { wakeUp(true); return; }
        recordInput(); drag.active = true;
        const c = e.touches ? e.touches[0] : e;
        const r = PET.$root[0].getBoundingClientRect();
        drag.x = c.clientX; drag.y = c.clientY; drag.t = now(); drag.ox = c.clientX - r.left; drag.oy = c.clientY - r.top;
        if (!e.touches) e.preventDefault();
    }
    function onMove(e) {
        recordInput(); if (!drag.active) return;
        const c = e.touches ? e.touches[0] : e;
        if (PET.state === 'DRAG') {
            PET.$root.css({ left: c.clientX - drag.ox, top: c.clientY - drag.oy, bottom: 'auto' });
            if (e.cancelable && e.touches) e.preventDefault();
            return;
        }
        if (Math.sqrt(Math.pow(c.clientX - drag.x, 2) + Math.pow(c.clientY - drag.y, 2)) > 5 && f.enableDrag) {
            stopAll(); PET.state = 'DRAG'; PET.$root.addClass('dragging');
            say(PET.isGhost ? '飞高高~' : '放开我！', 9000);
        }
    }
    function onUp() {
        if (!drag.active) return; drag.active = false;
        if (PET.state === 'DRAG') {
            PET.state = 'FALL'; PET.$root.removeClass('dragging');
            const r = PET.$root[0].getBoundingClientRect();
            PET.posX = clamp(r.left, 0, $(window).width() - PET.w);
            PET.posY = clamp($(window).height() - r.bottom, 0, $(window).height() - PET.h);
            PET.$root.css({ top: 'auto', bottom: PET.posY, left: PET.posX });
            PET.velY = 0; requestAnimationFrame(physicsLoop);
        } else handleClick();
    }

    function handleClick() {
        if (PET.state !== 'IDLE') return;
        if (f.enableToy && Math.random() < (cfg.toyChance || 0.65)) { dropToy(); return; }
        if (f.enableAttack && now() - PET.lastAttackTime > 30000 && Math.random() < (cfg.attackChance || 0.05)) { triggerAttack(); return; }

        sayRandom();
        PET.$body.addClass('sgn-act-jump'); setTimeout(() => PET.$body.removeClass('sgn-act-jump'), 600);
    }

    PET.$root.on('mousedown touchstart', onDown);
    $(document).on('mousemove touchmove', onMove);
    $(document).on('mouseup touchend touchcancel', onUp);

    function physicsLoop() {
        if (PET.state !== 'FALL') return;
        const tY = PET.isGhost ? PET.floatHeight : PET.ground;
        if (PET.isGhost) {
            const d = tY - PET.posY; PET.posY += d * 0.06;
            if (Math.abs(d) < 0.8) { PET.posY = tY; PET.$root.css('bottom', PET.posY); PET.state = 'IDLE'; scheduleNext(); return; }
        } else {
            PET.velY -= PET.gravity; PET.posY += PET.velY;
            if (PET.posY <= tY) {
                PET.posY = tY; PET.velY *= -PET.bounce;
                if (Math.abs(PET.velY) < 2) {
                    PET.state = 'IDLE'; PET.$root.css('bottom', PET.posY);
                    say('着陆成功。', 1500); scheduleNext(); return;
                }
            }
        }
        PET.$root.css('bottom', PET.posY); requestAnimationFrame(physicsLoop);
    }

    // --- Interactions ---
    function walkTo(targetX, speed, cb) {
        if (PET.state !== 'IDLE') return;
        PET.state = 'WALK';
        const winW = $(window).width();
        const x = clamp(targetX, 0, winW - PET.w);
        if (PET.isGhost) PET.$root.addClass('moving');
        faceToX(x);
        if (!PET.isGhost) PET.$body.css('animation', 'sgn-walk-bounce 0.2s infinite');
        const dur = Math.max(300, Math.abs(x - (parseFloat(PET.$root.css('left')) || 0)) * (PET.isGhost ? 8 : 5) / (speed || 1));
        PET.$root.animate({ left: x }, dur, 'linear', () => {
            PET.$body.css('animation', ''); PET.$root.removeClass('moving'); PET.state = 'IDLE';
            if (cb) cb();
        });
    }

    function dropToy() {
        if (!f.enableToy || PET.state !== 'IDLE') return;
        PET.state = 'CHASE'; clearTimeout(PET.timer);
        const label = (cfg.labels && cfg.labels.toy && cfg.labels.toy[cfg.model]) || '好吃的';
        say(label + '！是我的！', 1500);

        const winW = $(window).width();
        const winH = $(window).height();
        const tx = clamp(Math.random() * (winW - 100), 0, winW - 40);

        PET.$toy.css({ left: tx, top: -50, display: 'block' }).animate({ top: winH - 40 }, 600, 'swing', () => {
            PET.state = 'IDLE';
            walkTo(tx, PET.isGhost ? 1.5 : 2.5, () => {
                PET.state = 'CHASE';
                PET.$body.addClass('sgn-act-eat');
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

    function triggerAttack() {
        if (!f.enableAttack || PET.state !== 'IDLE') return;
        PET.state = 'ATTACK'; PET.lastAttackTime = now();
        say('我要闹了！', 1000);
        setTimeout(() => {
            $('body').addClass('sgn-glitch-active');
            PET.$effect.show().empty();
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

    function aiLoop() {
        if (PET.state !== 'IDLE') return;
        if (shouldSleep()) { enterSleep(); return; }
        const r = Math.random();

        if (Math.random() < (cfg.actionChance || 0)) {
            const acts = ['sit', 'spin', 'jump', 'stretch'];
            const act = acts[Math.floor(Math.random() * acts.length)];
            PET.$body.addClass('sgn-act-' + act);
            setTimeout(() => { PET.$body.removeClass('sgn-act-' + act); scheduleNext(); }, 1000);
            return;
        }

        if (r < 0.4) {
            walkTo(Math.random() * ($(window).width() - 100), 1, () => {
                if (Math.random() < 0.3) sayRandom();
                scheduleNext();
            });
        } else {
            if (Math.random() < (cfg.talkChance || 0.45)) sayRandom();
            scheduleNext();
        }
    }
    function scheduleNext(min, jitter) { clearTimeout(PET.timer); PET.timer = setTimeout(aiLoop, (min || 2500) + Math.random() * (jitter || 3500)); }

    // --- Listeners ---

    // 1. 离屏吐槽 (自定义文案)
    if (f.enableVisibility) {
        let originTitle = document.title;
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                originTitle = document.title;
                document.title = txt.titleLost || '🥺 人呢？';
            } else {
                document.title = txt.titleBack || '🎉 欢迎回来！';
                setTimeout(() => { document.title = originTitle; }, 2000);
                if (PET.state !== 'SLEEP' && txt.msgBack) say(txt.msgBack, 3000);
            }
        });
    }

    // 2. 滚动
    if (f.enableScroll) {
        let stT;
        $(window).on('scroll', () => {
            recordInput(); if (stT) return; stT = setTimeout(() => { stT = null }, 50);
            if (PET.state !== 'IDLE' || PET.isGhost) return;
            const t = $(window).scrollTop(), d = t - PET.lastScrollTop;
            if (Math.abs(d) > (cfg.scrollThreshold || 60)) {
                stopAll(); PET.$root.addClass('sgn-falling');
                PET.$root.css('bottom', d > 0 ? '-20px' : '50px');
                say(d > 0 ? '啊啊啊~' : '飞起来了！', 800);
                setTimeout(() => { PET.$root.removeClass('sgn-falling'); PET.$root.css('bottom', 0); }, 600);
            }
            PET.lastScrollTop = t;
        });
    }

    // 3. 伴读
    if (f.enableReading) {
        $(document).on('mouseenter', 'a,p', function () {
            if (PET.state !== 'IDLE') return;
            if (Math.random() > (cfg.readingChance || 0.3)) return;
            const t = $(this).text().trim().substring(0, 10);
            if (t.length > 1) say('在看“' + t + '”吗？');
        });
    }

    $(document).on('mousemove', e => {
        if (PET.state === 'SLEEP') {
            if (Math.abs(e.clientX - PET.lastMouseX) > 50) { PET.mouseShakeCount++; if (PET.mouseShakeCount > 10) wakeUp(true); }
            PET.lastMouseX = e.clientX;
        }
    });

    $(function () {
        if (PET.isGhost) { PET.posY = PET.floatHeight; PET.$root.css('bottom', PET.posY); }
        setFacing('left');

        // 4. 时间段问候
        const h = new Date().getHours();
        if (h >= 6 && h < 11 && txt.morning) say(txt.morning, 4000);
        else if (h >= 11 && h < 14 && txt.noon) say(txt.noon, 4000);
        else if (h >= 20 && h < 24 && txt.night) say(txt.night, 4000);

        scheduleNext(1000, 2000);
    });
})(jQuery);