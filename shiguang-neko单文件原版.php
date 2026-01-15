<?php
/**
 * Plugin Name: Shiguang Neko (Ultimate Fusion V4.8)
 * Plugin URI:  https://www.shiguang.ink
 * Description: 像素互动宠物 V4.8。修复了因缺失 jQuery UI 导致的喂食卡死问题，找回了鸽子的嘴巴，完美复刻 V3 经典模型与失焦互动。
 * Version:     4.8.0
 * Author:      Shiguang
 * License:     GPL2
 */

if (!defined('ABSPATH')) exit;

// ==========================================
// 1. 后台设置
// ==========================================

class Shiguang_Neko_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page() {
        add_options_page('时光宠物 V4.8 设置', '时光宠物 V4.8', 'manage_options', 'shiguang-neko', array($this, 'create_admin_page'));
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>👾 时光宠物 (Ultimate Fusion) V4.8 最终修复版</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('neko_option_group');
                do_settings_sections('shiguang-neko');
                submit_button();
                ?>
            </form>
            <div style="margin-top:18px; padding: 12px; background:#fff; border:1px solid #e5e5e5;">
                <h3>✨ V4.8 修复日志</h3>
                <p>1. <strong>紧急修复喂食</strong>：移除了导致动画卡死的 `easeOutBounce` 依赖，重写了原生 JS 的掉落+弹跳算法。现在点击喂食 100% 触发。</p>
                <p>2. <strong>鸽子整形</strong>：找回了鸽子丢失的橘色嘴巴像素块。</p>
                <p>3. <strong>体验优化</strong>：保留了 V4.7 的“网页失焦卖萌”功能（标题栏变化）。</p>
            </div>
        </div>
        <?php
    }

    public function page_init() {
        register_setting('neko_option_group', 'neko_options');
        add_settings_section('neko_main', '基础设置', null, 'shiguang-neko');
        add_settings_section('neko_quotes', '语录配置', null, 'shiguang-neko');

        add_settings_field('model_type', '选择宠物', array($this, 'field_model_type'), 'shiguang-neko', 'neko_main');
        add_settings_field('mobile_display', '移动端显示', array($this, 'field_mobile_display'), 'shiguang-neko', 'neko_main');
        add_settings_field('api_url', '一言 API', array($this, 'field_api_url'), 'shiguang-neko', 'neko_main');

        add_settings_field('cat_sentences', '🐈 黑猫语录', array($this, 'field_cat_sentences'), 'shiguang-neko', 'neko_quotes');
        add_settings_field('bird_sentences', '🐦 鸽子语录', array($this, 'field_bird_sentences'), 'shiguang-neko', 'neko_quotes');
        add_settings_field('dog_sentences', '🐶 柯基语录', array($this, 'field_dog_sentences'), 'shiguang-neko', 'neko_quotes');
        add_settings_field('ghost_sentences', '👻 幽灵语录', array($this, 'field_ghost_sentences'), 'shiguang-neko', 'neko_quotes');
    }

    public function field_model_type() {
        $options = get_option('neko_options');
        $current = isset($options['model_type']) ? $options['model_type'] : 'cat';
        ?>
        <label style="margin-right: 10px;"><input type="radio" name="neko_options[model_type]" value="cat" <?php checked('cat', $current); ?>> 🐈 黑猫</label>
        <label style="margin-right: 10px;"><input type="radio" name="neko_options[model_type]" value="pigeon" <?php checked('pigeon', $current); ?>> 🐦 鸽子</label>
        <label style="margin-right: 10px;"><input type="radio" name="neko_options[model_type]" value="dog" <?php checked('dog', $current); ?>> 🐶 柯基</label>
        <label style="margin-right: 10px;"><input type="radio" name="neko_options[model_type]" value="ghost" <?php checked('ghost', $current); ?>> 👻 幽灵</label>
        <label><input type="radio" name="neko_options[model_type]" value="random" <?php checked('random', $current); ?>> 🎲 随机</label>
        <?php
    }

    public function field_mobile_display() {
        $options = get_option('neko_options');
        $val = isset($options['mobile_display']) ? $options['mobile_display'] : '0';
        echo '<label><input type="checkbox" name="neko_options[mobile_display]" value="1" ' . checked('1', $val, false) . '> 在手机端隐藏</label>';
    }

    public function field_api_url() {
        $options = get_option('neko_options');
        $val = isset($options['api_url']) ? $options['api_url'] : 'https://v1.hitokoto.cn/?c=b';
        echo "<input type='text' name='neko_options[api_url]' value='" . esc_attr($val) . "' style='width:100%; max-width:500px;' />";
    }

    private function render_textarea($key, $default) {
        $options = get_option('neko_options');
        $val = isset($options[$key]) ? $options[$key] : $default;
        echo "<textarea name='neko_options[$key]' rows='4' style='width:100%; max-width:500px;'>" . esc_textarea($val) . "</textarea>";
    }

    public function field_cat_sentences() { $this->render_textarea('cat_sentences', "鱼唇的人类...\n把你的屏幕抓花！\n今天也是摸鱼的一天。\n不准离开我的视线！\n我要小鱼干！\nZZZ...好困。"); }
    public function field_bird_sentences() { $this->render_textarea('bird_sentences', "咕咕咕？\n人类，有面包屑吗？\n只要我飞得够快，ddl就追不上我。\n我想去码头整点薯条。\n不要抓我煲汤！"); }
    public function field_dog_sentences() { $this->render_textarea('dog_sentences', "汪汪！(摇尾巴)\n主人在看什么？我也要看！\n飞盘！我要玩飞盘！\n可以带我出去散步吗？\n这里有好闻的味道！"); }
    public function field_ghost_sentences() { $this->render_textarea('ghost_sentences', "我死得好惨...\n啊不是，是饿得好惨。\n你看不到我...你看不到我...\n飘啊飘~\n穿墙术！(撞头)"); }
}
if (is_admin()) new Shiguang_Neko_Settings();

// ==========================================
// 2. 核心渲染 (CSS整合)
// ==========================================

add_action('wp_footer', 'shiguang_neko_render');

function shiguang_neko_render() {
    $options = get_option('neko_options');

    $model_option = isset($options['model_type']) ? $options['model_type'] : 'cat';
    $model = $model_option;
    if($model_option === 'random') {
        $all = array('cat','pigeon','dog','ghost');
        $model = $all[array_rand($all)];
    }

    $hideMobile = isset($options['mobile_display']) && $options['mobile_display'] == '1';
    $quotesKey = $model . '_sentences';
    $rawQuotes = isset($options[$quotesKey]) ? $options[$quotesKey] : '';
    $quotes = array_filter(array_map('trim', explode("\n", $rawQuotes)));
    if(empty($quotes)) $quotes = ["..."];

    $config = json_encode(array(
        'model' => $model,
        'apiUrl' => isset($options['api_url']) ? $options['api_url'] : '',
        'sentences' => array_values($quotes),
    ));
    ?>

    <style>
        /* --- 容器 --- */
        #sg-pet-root {
            position: fixed; z-index: 99999;
            width: 64px; height: 64px; 
            bottom: 0; left: 20px; 
            cursor: grab;
            image-rendering: pixelated; 
            user-select: none;
            -webkit-user-drag: none;
            touch-action: none;
            will-change: left, bottom, top;
        }
        #sg-pet-root.dragging { cursor: grabbing; transition: none !important; }
        .sg-model-ghost #sg-pet-root { opacity: 0.85; }
        
        #sg-pet-flipper {
            width: 100%; height: 100%;
            transition: transform 0.1s linear;
            transform-origin: center center;
            pointer-events: none;
        }

        #sg-pet-body { width: 100%; height: 100%; pointer-events: none; }
        
        <?php if($hideMobile): ?>
        @media screen and (max-width: 768px) { #sg-pet-root { display: none !important; } }
        <?php endif; ?>

        .sg-pixel-svg { width: 100%; height: 100%; filter: drop-shadow(0 4px 2px rgba(0,0,0,0.3)); }

        /* --- 气泡 --- */
        #sg-pet-msg {
            position: absolute; bottom: 80px; 
            left: 50%; transform: translateX(-50%);
            width: max-content; max-width: 220px;
            background: rgba(0,0,0,0.85); color: #fff; font-size: 12px; line-height: 1.4;
            padding: 8px 10px; border-radius: 10px; 
            opacity: 0; pointer-events: none; transition: opacity .25s ease, bottom .25s ease;
            word-break: break-word; white-space: pre-wrap; z-index: 100001;
        }
        #sg-pet-msg:after {
            content:""; position:absolute; left: 50%; bottom:-8px; transform: translateX(-50%);
            width:0; height:0; border-width:8px 8px 0; border-style: solid; border-color: rgba(0,0,0,0.85) transparent transparent;
        }
        #sg-pet-msg.show { opacity: 1; bottom: 95px; }

        /* --- 睡眠 --- */
        #sg-pet-sleep {
            position: absolute; left: 50%; bottom: 70px; transform: translateX(-50%);
            font-family: 'Comic Sans MS', cursive, sans-serif; font-weight: bold;
            font-size: 24px; color: #8a2be2; text-shadow: 2px 2px 0 #fff;
            opacity: 0; pointer-events: none;
        }
        #sg-pet-root.sleeping #sg-pet-sleep { animation: sg-zzz 2s infinite; opacity: 1; }

        /* --- 动画库 --- */
        @keyframes sg-walk-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        @keyframes sg-tail { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(10deg); } }
        @keyframes sg-blink { 0%, 92%, 100% { transform: scaleY(1); } 94% { transform: scaleY(0.2); } }
        @keyframes sg-dog-wag { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-15deg); } 75% { transform: rotate(15deg); } }
        
        @keyframes sg-jump { 0% { transform: translateY(0); } 35% { transform: translateY(-16px); } 100% { transform: translateY(0); } }
        @keyframes sg-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes sg-sit { 0% { transform: translateY(0); } 100% { transform: translateY(4px); } }
        
        @keyframes sg-sniff { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }
        @keyframes sg-peck { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(6px); } }
        @keyframes sg-stretch { 0% { transform: scaleX(1) translateY(0); } 50% { transform: scaleX(1.10) translateY(-2px); } 100% { transform: scaleX(1) translateY(0); } }
        @keyframes sg-eat { 0%, 100% { transform: translateY(0) rotate(0deg); } 35% { transform: translateY(2px) rotate(-6deg); } 70% { transform: translateY(2px) rotate(6deg); } }
        @keyframes sg-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes sg-zzz { 
            0% { transform: translateX(-50%) scale(0.5) translateY(10px); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(-30%) scale(1.2) translateY(-20px); opacity: 0; }
        }

        #sg-pet-body.sg-act-jump { animation: sg-jump 0.6s ease-out; }
        #sg-pet-body.sg-act-spin { animation: sg-spin 0.8s linear; transform-origin: 50% 75%; }
        #sg-pet-body.sg-act-sit { animation: sg-sit 0.6s ease-out forwards; }
        #sg-pet-body.sg-act-sniff { animation: sg-sniff 0.8s ease-in-out; }
        #sg-pet-body.sg-act-peck { animation: sg-peck 0.7s ease-in-out; }
        #sg-pet-body.sg-act-stretch { animation: sg-stretch 1.0s ease-in-out; }
        #sg-pet-body.sg-act-eat { animation: sg-eat 0.9s ease-in-out; transform-origin: 50% 80%; }

        #sg-pet-root.sleeping #sg-pet-body {
            filter: grayscale(0.5);
            transition: transform 0.5s ease;
            transform: scaleY(0.6) translateY(20px); 
            animation: none !important; 
        }
        
        .sg-model-ghost #sg-pet-body { animation: sg-float 3s infinite ease-in-out; }
        .sg-model-ghost.sleeping #sg-pet-body { transform: scale(0.9); opacity: 0.5; animation: sg-float 5s infinite ease-in-out; }
        .sg-model-ghost.moving #sg-pet-root { opacity: 0.6; }

        /* --- 道具增强 --- */
        #sg-toy-layer, #sg-effect-layer { position: fixed; top:0; left:0; width:100vw; height:100vh; pointer-events:none; z-index:99998; }
        .sg-toy { 
            position: fixed; width: 36px; height: 36px; z-index: 99998; 
            display: none; image-rendering: pixelated; 
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
        }
        .sg-claw-mark { position: absolute; background: #000; opacity: 0.8; transform: rotate(-45deg); width: 300px; height: 40px; }
        
        body.sg-glitch-active { animation: sg-glitch-body 0.2s infinite; }
        @keyframes sg-glitch-body { 
            0% { transform: translate(0); filter: hue-rotate(0deg); } 
            25% { transform: translate(-3px, 3px); filter: invert(0.2); } 
            100% { transform: translate(0); filter: none; } 
        }
    </style>

    <div id="sg-pet-root" class="sg-model-<?php echo esc_attr($model); ?>">
        <div id="sg-pet-msg">...</div>
        <div id="sg-pet-sleep">Zzz...</div>
        
        <div id="sg-pet-flipper">
            <div id="sg-pet-body">
                <?php if($model === 'cat'): ?>
                    <svg viewBox="0 0 32 32" class="sg-pixel-svg" shape-rendering="crispEdges">
                        <g style="transform-origin: 26px 22px; animation: sg-tail 2s infinite ease-in-out;">
                            <rect x="26" y="20" width="2" height="6" fill="#000"/>
                            <rect x="28" y="18" width="2" height="4" fill="#000"/>
                        </g>
                        <rect x="6" y="14" width="20" height="14" rx="2" fill="#0E0E0E"/>
                        <rect x="6" y="28" width="4" height="2" fill="#000"/>
                        <rect x="22" y="28" width="4" height="2" fill="#000"/>
                        <rect x="4" y="8" width="16" height="12" rx="1" fill="#000"/>
                        <path d="M4 8 L4 4 L8 8 Z" fill="#000"/>
                        <path d="M20 8 L20 4 L16 8 Z" fill="#000"/>
                        <g style="transform-origin: 12px 14px; animation: sg-blink 4s infinite;">
                            <rect x="7" y="12" width="2" height="2" fill="#FFD700"/>
                            <rect x="15" y="12" width="2" height="2" fill="#FFD700"/>
                        </g>
                        <rect x="11" y="15" width="2" height="1" fill="#FF69B4"/>
                    </svg>

                <?php elseif($model === 'pigeon'): ?>
                    <svg viewBox="0 0 32 32" class="sg-pixel-svg" shape-rendering="crispEdges">
                        <path d="M10 16 h14 v10 h-14 Z" fill="#D3D3D3"/>
                        <path d="M12 18 h10 v6 h-10 Z" fill="#A9A9A9"/>
                        <rect x="14" y="20" width="6" height="1" fill="#808080"/>
                        <rect x="14" y="22" width="6" height="1" fill="#808080"/>
                        <path d="M6 18 l4 4 v-6 Z" fill="#696969"/>
                        <rect x="20" y="8" width="8" height="8" fill="#D3D3D3"/>
                        <rect x="22" y="10" width="1" height="1" fill="#000"/>
                        <rect x="28" y="11" width="2" height="2" fill="#FFA500"/> <rect x="20" y="15" width="8" height="1" fill="#556B2F"/>
                        <rect x="14" y="26" width="2" height="4" fill="#FFA500"/>
                        <rect x="20" y="26" width="2" height="4" fill="#FFA500"/>
                    </svg>

                <?php elseif($model === 'dog'): ?>
                    <svg viewBox="0 0 32 32" class="sg-pixel-svg" shape-rendering="crispEdges">
                        <g style="transform-origin: 28px 20px; animation: sg-dog-wag 0.8s infinite ease-in-out;">
                            <rect x="28" y="18" width="3" height="2" fill="#D08A2D"/>
                            <rect x="30" y="17" width="1" height="4" fill="#B06F24"/>
                        </g>
                        <rect x="10" y="16" width="16" height="10" rx="2" fill="#D08A2D"/>
                        <rect x="12" y="19" width="10" height="5" rx="1" fill="#F5F5F5"/>
                        <rect x="10" y="26" width="4" height="4" fill="#B06F24"/>
                        <rect x="22" y="26" width="4" height="4" fill="#B06F24"/>
                        <rect x="3" y="14" width="9" height="9" rx="2" fill="#D08A2D"/>
                        <rect x="4" y="12" width="3" height="3" fill="#B06F24"/>
                        <rect x="9" y="12" width="3" height="3" fill="#B06F24"/>
                        <rect x="7" y="17" width="1" height="1" fill="#000"/>
                        <rect x="3" y="19" width="2" height="2" fill="#000"/>
                        <rect x="5" y="21" width="2" height="1" fill="#FF69B4"/>
                    </svg>

                <?php elseif($model === 'ghost'): ?>
                    <svg viewBox="0 0 32 32" class="sg-pixel-svg" shape-rendering="crispEdges">
                        <path d="M8 10 Q8 2 16 2 Q24 2 24 10 V24 L21 21 L18 24 L16 21 L13 24 L10 21 L8 24 Z" fill="#FFFFFF"/>
                        <rect x="11" y="10" width="3" height="3" fill="#000"/>
                        <rect x="18" y="10" width="3" height="3" fill="#000"/>
                        <rect x="10" y="14" width="2" height="1" fill="#FFC0CB" opacity="0.6"/>
                        <rect x="20" y="14" width="2" height="1" fill="#FFC0CB" opacity="0.6"/>
                    </svg>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="sg-toy-layer">
        <div id="sg-toy-item" class="sg-toy">
            <?php if($model === 'cat'): ?>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#E53935"/><path d="M7 7 L17 17" stroke="#FFF" stroke-width="2"/><path d="M17 7 L7 17" stroke="#FFF" stroke-width="2"/></svg>
            <?php elseif($model === 'pigeon'): ?>
                <svg viewBox="0 0 24 24"><path d="M4 12 Q4 4 12 4 Q20 4 20 12 L20 18 Q20 20 18 20 L6 20 Q4 20 4 18 Z" fill="#F5C16C"/><circle cx="9" cy="12" r="1.5" fill="#000"/></svg>
            <?php else: ?>
                <svg viewBox="0 0 24 24" style="filter: drop-shadow(1px 1px 0 #ccc);"><path d="M6 10 L10 8 L14 8 L18 10 L18 14 L14 16 L10 16 L6 14 Z" fill="#F5F5F5"/><circle cx="6" cy="12" r="3" fill="#F5F5F5"/><circle cx="18" cy="12" r="3" fill="#F5F5F5"/></svg>
            <?php endif; ?>
        </div>
    </div>
    <div id="sg-effect-layer"></div>

    <script>
    (function($){
        const cfg = <?php echo $config; ?>;
        let originalTitle = document.title;
        
        // --- 核心状态机 ---
        const PET = {
            $root: $('#sg-pet-root'),
            $msg: $('#sg-pet-msg'),
            $flipper: $('#sg-pet-flipper'),
            $body: $('#sg-pet-body'),
            $toy: $('#sg-toy-item'),
            $effect: $('#sg-effect-layer'),
            
            w: 64, h: 64,
            ground: 0, 
            floatHeight: 50,
            isGhost: (cfg.model === 'ghost'),
            
            state: 'IDLE', 
            timer: null,
            
            isMouseDown: false,
            isDragMove: false,
            dragStartX: 0, dragStartY: 0,
            dragOffset: {x:0, y:0},
            
            lastInputTime: Date.now(),
            mouseShakeCount: 0,
            lastMouseX: 0,
            
            posX: 20, posY: 0, 
            velY: 0,
            gravity: 1.5,
            bounce: 0.6
        };

        // ----------------------------------------
        // 0. 页面可见性 (标题卖萌)
        // ----------------------------------------
        document.addEventListener("visibilitychange", function() {
            if (document.hidden) {
                clearTimeout(PET.timer);
                originalTitle = document.title;
                document.title = '( >﹏< ) 你去哪了?';
                if(PET.state === 'WALK') stopAllActions();
            } else {
                document.title = '(*´∇｀*) 你回来啦!';
                setTimeout(() => { document.title = originalTitle; }, 3000);
                PET.lastInputTime = Date.now(); 
                if(PET.state === 'SLEEP') wakeUp(false);
                else say("你回来啦！", 2000);
                scheduleNext(1000);
            }
        });

        // ----------------------------------------
        // 1. 物理引擎与拖拽
        // ----------------------------------------
        PET.$root.on('mousedown', function(e) {
            e.preventDefault();
            PET.isMouseDown = true;
            PET.isDragMove = false;
            PET.dragStartX = e.clientX;
            PET.dragStartY = e.clientY;
            
            let rect = PET.$root[0].getBoundingClientRect();
            PET.dragOffset.x = e.clientX - rect.left;
            PET.dragOffset.y = e.clientY - rect.top;
        });

        $(document).on('mousemove', function(e) {
            PET.lastInputTime = Date.now();
            
            if (PET.state === 'SLEEP') {
                if (Math.abs(e.clientX - PET.lastMouseX) > 50) {
                    PET.mouseShakeCount++;
                    if (PET.mouseShakeCount > 8) wakeUp(true);
                }
                PET.lastMouseX = e.clientX;
            }

            if (PET.isMouseDown && !PET.isDragMove) {
                if (Math.abs(e.clientX - PET.dragStartX) > 5 || Math.abs(e.clientY - PET.dragStartY) > 5) {
                    startDrag();
                }
            }

            if (PET.state === 'DRAG') {
                let x = e.clientX - PET.dragOffset.x;
                let y = e.clientY - PET.dragOffset.y;
                let maxW = $(window).width() - PET.w;
                let maxH = $(window).height() - PET.h;
                if(x < 0) x = 0; if(x > maxW) x = maxW;
                if(y < 0) y = 0; if(y > maxH) y = maxH;
                PET.$root.css({left: x, top: y, bottom: 'auto'});
            }
        });

        $(document).on('mouseup', function(e) {
            PET.isMouseDown = false;
            if (PET.state === 'DRAG') {
                stopDrag();
            } else if (!PET.isDragMove && $(e.target).closest('#sg-pet-root').length > 0) {
                handleClick();
            }
        });

        function startDrag() {
            if (PET.state === 'SLEEP') wakeUp(true);
            stopAllActions();
            PET.state = 'DRAG';
            PET.isDragMove = true;
            PET.$root.addClass('dragging');
            say(PET.isGhost ? "飘起来咯~" : "哎哎哎！放手！", 10000);
        }

        function stopDrag() {
            PET.state = 'FALL';
            PET.$root.removeClass('dragging');
            let rect = PET.$root[0].getBoundingClientRect();
            let winH = $(window).height();
            let winW = $(window).width();
            PET.posX = rect.left;
            PET.posY = winH - rect.bottom; 
            if (PET.posY < 0) PET.posY = 0;
            if (PET.posX < 0) PET.posX = 0;
            if (PET.posX > winW - PET.w) PET.posX = winW - PET.w;
            PET.$root.css({ top: 'auto', bottom: PET.posY, left: PET.posX });
            PET.velY = 0;
            requestAnimationFrame(physicsLoop);
        }

        function physicsLoop() {
            if (PET.state !== 'FALL') return;
            if (document.hidden) { requestAnimationFrame(physicsLoop); return; }

            let targetY = PET.isGhost ? PET.floatHeight : PET.ground;
            
            if (PET.isGhost) {
                let diff = targetY - PET.posY;
                PET.posY += diff * 0.05;
                if (Math.abs(diff) < 1) {
                    PET.posY = targetY;
                    PET.state = 'IDLE';
                    scheduleNext();
                }
            } else {
                PET.velY -= PET.gravity;
                PET.posY += PET.velY;
                if (PET.posY <= targetY) {
                    PET.posY = targetY;
                    PET.velY *= -PET.bounce;
                    if (Math.abs(PET.velY) < 2) {
                        PET.state = 'IDLE';
                        say("晕死我了...");
                        scheduleNext();
                        return;
                    }
                }
            }
            PET.$root.css('bottom', PET.posY);
            if (PET.state === 'FALL') requestAnimationFrame(physicsLoop);
        }

        // ----------------------------------------
        // 2. 睡眠系统
        // ----------------------------------------
        setInterval(checkSleepCondition, 60000); 

        function checkSleepCondition() {
            if (PET.state === 'DRAG' || PET.state === 'FALL' || PET.state === 'CHASE') return;
            if (document.hidden) return;

            let now = new Date();
            let hour = now.getHours();
            let isNight = (hour >= 23 || hour < 6);
            let isIdle = (Date.now() - PET.lastInputTime > 300000); 

            if ((isNight || isIdle) && PET.state !== 'SLEEP') enterSleep();
        }

        function enterSleep() {
            stopAllActions();
            PET.state = 'SLEEP';
            PET.$root.addClass('sleeping');
            PET.mouseShakeCount = 0;
            if(!PET.isGhost) PET.$root.css('bottom', 0);
        }

        function wakeUp(isRude) {
            PET.state = 'IDLE';
            PET.$root.removeClass('sleeping');
            PET.lastInputTime = Date.now();
            if(isRude) {
                say("吵死啦！！", 2000);
                PET.$body.addClass('sg-act-jump');
                setTimeout(()=>PET.$body.removeClass('sg-act-jump'), 500);
            } else {
                say("哈欠... 早安。", 2000);
            }
            scheduleNext();
        }

        // ----------------------------------------
        // 3. 互动与动作
        // ----------------------------------------
        function say(text, duration = 3000) {
            PET.$msg.html(text).addClass('show');
            clearTimeout(PET.msgTimer);
            PET.msgTimer = setTimeout(() => PET.$msg.removeClass('show'), duration);
        }

        function sayRandom() {
            if(cfg.apiUrl && Math.random() < 0.7) {
                $.ajax({ url: cfg.apiUrl, dataType:'json', timeout: 2000 })
                .done(d => {
                    let t = d.hitokoto || d.text || d.content;
                    if(t) say(t); else sayLocal();
                })
                .fail(() => sayLocal());
            } else {
                sayLocal();
            }
        }
        function sayLocal() {
            say(cfg.sentences[Math.floor(Math.random() * cfg.sentences.length)]);
        }

        function setFacing(dir) {
            let scale = (dir === 'right') ? -1 : 1;
            if (cfg.model === 'pigeon') scale *= -1; 
            PET.$flipper.css('transform', `scaleX(${scale})`);
        }

        function walkTo(targetX, cb) {
            PET.state = 'WALK';
            if(PET.isGhost) PET.$root.addClass('moving');
            let curX = parseFloat(PET.$root.css('left'));
            let dist = Math.abs(targetX - curX);
            let duration = dist * (PET.isGhost ? 8 : 5);
            setFacing(targetX > curX ? 'right' : 'left');
            if(!PET.isGhost) PET.$body.css('animation', 'sg-walk-bounce 0.2s infinite');
            PET.$root.animate({left: targetX}, duration, 'linear', function(){
                PET.$body.css('animation', '');
                PET.$root.removeClass('moving');
                PET.state = 'IDLE';
                if(cb) cb();
            });
        }

        function doRandomAction() {
            if(PET.state !== 'IDLE') return;
            PET.state = 'ACT';
            const actionsByModel = {
                cat: [
                    {a: 'stretch', d: 1000, t: '伸个懒腰~'}, {a: 'jump', d: 700}, {a: 'spin', d: 900}, {a: 'sit', d: 2000, t: '我坐会儿。'}, {a: 'sniff', d: 800}
                ],
                pigeon: [
                    {a: 'peck', d: 700, t: '啄啄~'}, {a: 'spin', d: 900}, {a: 'sniff', d: 800}
                ],
                dog: [
                    {a: 'spin', d: 900, t: '转圈圈！'}, {a: 'jump', d: 700, t: '汪！'}, {a: 'sit', d: 2000, t: '坐下！'}
                ],
                ghost: [
                    {a: 'spin', d: 1000, t: '幽灵回旋！'}, {a: 'jump', d: 1000, t: 'Boo!'}
                ]
            };
            const list = actionsByModel[cfg.model] || actionsByModel.cat;
            const pick = list[Math.floor(Math.random() * list.length)];
            PET.$body.removeClass('sg-act-jump sg-act-spin sg-act-sit sg-act-sniff sg-act-peck sg-act-stretch sg-act-eat');
            void PET.$body[0].offsetWidth; 
            PET.$body.addClass('sg-act-' + pick.a);
            if(pick.t && Math.random() < 0.7) say(pick.t, pick.d);
            setTimeout(() => {
                PET.$body.removeClass('sg-act-' + pick.a);
                PET.state = 'IDLE';
                scheduleNext();
            }, pick.d);
        }

        function triggerAttack() {
            PET.state = 'ACT';
            let act = cfg.model === 'dog' ? '挖掘' : '抓挠';
            say("我要" + act + "了！");
            $('body').addClass('sg-glitch-active');
            PET.$effect.show().empty();
            for(let i=0;i<4;i++){
                let $m = $('<div class="sg-claw-mark"></div>');
                $m.css({left: Math.random()*(window.innerWidth-200), top: Math.random()*(window.innerHeight-40)});
                PET.$effect.append($m);
            }
            setTimeout(() => {
                $('body').removeClass('sg-glitch-active');
                PET.$effect.hide().empty();
                PET.state = 'IDLE';
                scheduleNext(1000);
            }, 1200);
        }

        function stopAllActions() {
            PET.$root.stop(true);
            PET.$body.css('animation', '').removeClass('sg-act-jump sg-act-spin sg-act-sit sg-act-sniff sg-act-peck sg-act-stretch sg-act-eat');
            PET.$root.removeClass('moving sleeping sg-falling');
            clearTimeout(PET.timer);
        }

        function aiLoop() {
            if(PET.state !== 'IDLE') return;
            if (document.hidden) return;
            checkSleepCondition();
            if(PET.state === 'SLEEP') return;
            const r = Math.random();
            if(r < 0.25) { 
                let maxW = $(window).width() - 80;
                walkTo(Math.random() * maxW, () => scheduleNext());
            } else if (r < 0.50) { 
                sayRandom();
                scheduleNext();
            } else { 
                doRandomAction();
            }
        }

        function scheduleNext(ms) {
            clearTimeout(PET.timer);
            PET.timer = setTimeout(aiLoop, ms || (3000 + Math.random() * 4000));
        }

        function handleClick() {
            if(PET.state === 'SLEEP') {
                wakeUp(true);
                return;
            }
            if(PET.state !== 'IDLE') return;
            let rand = Math.random();
            if(rand < 0.35) dropToy(); 
            else if(rand < 0.5) triggerAttack();
            else if(rand < 0.8) doRandomAction();
            else sayRandom();
        }

        // --- 修复：移除 easeOutBounce 依赖，使用原生链式动画模拟回弹 ---
        function dropToy() {
            if(PET.isGhost) { say("我不吃东西..."); return; }
            PET.state = 'CHASE';
            
            let winH = $(window).height();
            let tx = Math.random() * ($(window).width() - 100);
            
            say("好吃的！");
            PET.$toy.show().css({left: tx, top: -50, transform: 'scale(1)'});

            // 1. 掉落 (使用默认 swing)
            PET.$toy.animate({ top: winH - 60 }, {
                duration: 800, 
                easing: 'swing',
                step: function(now, fx) {
                    if(fx.prop === 'top') {
                        let rotate = (now / winH) * 720; // 掉落时旋转
                        $(this).css('transform', `rotate(${rotate}deg) scale(1.2)`);
                    }
                },
                complete: function() {
                    // 2. 落地回弹 (手动模拟 bounce)
                    $(this).animate({top: winH - 90}, 150) // 弹起
                           .animate({top: winH - 60}, 150, function(){ // 落下
                               // 3. 走过去吃
                               $(this).css('transform', 'none'); 
                               walkTo(tx, () => {
                                   PET.$body.addClass('sg-act-eat');
                                   say("啊呜啊呜~");
                                   setTimeout(() => {
                                       PET.$body.removeClass('sg-act-eat');
                                       PET.$toy.fadeOut();
                                       PET.state = 'IDLE';
                                       scheduleNext();
                                   }, 1000);
                               });
                           });
                }
            });
        }

        $('a, p').on('mouseenter', function() {
            if(PET.state === 'IDLE' && Math.random() < 0.05) {
                let t = $(this).text().trim().substring(0, 10);
                if(t.length > 2) say("在看这个吗？\n“" + t + "...”");
            }
        });

        $(document).ready(function(){
            if(PET.isGhost) {
                PET.posY = PET.floatHeight;
                PET.$root.css('bottom', PET.floatHeight);
            }
            let h = new Date().getHours();
            if(h<9) say("早安！");
            else if(h>22) say("还不睡吗？");

            scheduleNext();
        });

    })(jQuery);
    </script>
    <?php
}
?>