<?php
/**
 * Plugin Name: Shiguang Neko
 * Plugin URI:  https://www.shiguang.ink/
 * Description: 像素互动宠物：修复移动端掉落悬空、进食错位及标题恢复逻辑，体验更完美。
 * Version:     4.9.0
 * Author:      Shiguang
 * License:     GPL2
 */

if (!defined('ABSPATH')) exit;

define('SGN_VERSION', '4.9.0');

// ==========================================
// 1. 默认配置
// ==========================================
function sgn_defaults() {
    return array(
        'model_type' => 'cat',
        'mobile_display' => '0',
        'api_url' => 'https://v1.hitokoto.cn/?c=b',
        
        // 功能开关 & 时间设置
        'enable_drag' => '1',
        'enable_sleep' => '1',
        'enable_scroll' => '1',
        'enable_visibility' => '1',
        'enable_reading' => '1',
        'enable_toy' => '1',
        'enable_attack' => '1',
        'sleep_start' => '23',
        'sleep_end' => '6',
        
        // 行为调参 (概率 0-100)
        'action_chance' => '30',
        'talk_chance' => '45',
        'reading_chance' => '30',
        'toy_chance' => '65',
        'attack_chance' => '5',
        'idle_sleep_ms' => '300000',

        // 交互文案
        'title_lost' => '🥺 人呢？去哪了？',
        'title_back' => '🎉 欢迎回来！',
        'msg_back' => '你刚去哪了？我有在乖乖看家哦。',
        'greet_morning' => '早安！今天也要元气满满！',
        'greet_noon' => '午饭时间到，该喂我了！',
        'greet_night' => '还不睡吗？熬夜对皮肤不好哦。',

        // 随机语录
        'cat_sentences' => "鱼唇的人类...\n把你的屏幕抓花！\n今天也是摸鱼的一天。\n不准离开我的视线！\n我要毛线团！",
        'bird_sentences' => "咕咕咕？\n人类，有面包屑吗？\n只要我飞得够快，ddl就追不上我。\n我想去码头整点薯条。\n不要抓我煲汤！",
        'dog_sentences' => "汪汪！(摇尾巴)\n主人在看什么？我也要看！\n飞盘！我要玩飞盘！\n可以带我出去散步吗？\n这里有好闻的味道！",
        'ghost_sentences' => "我死得好惨...\n啊不是，是饿得好惨。\n你看不到我...你看不到我...\n飘啊飘~\n穿墙术！(撞头)"
    );
}

function sgn_get_options() {
    $saved = get_option('sgn_options');
    if (!is_array($saved)) $saved = array();
    return array_merge(sgn_defaults(), $saved);
}

function sgn_resolve_model($opt) {
    static $resolved = null;
    if ($resolved !== null) return $resolved;
    $model_option = isset($opt['model_type']) ? $opt['model_type'] : 'cat';
    if ($model_option === 'random') {
        $all = array('cat','pigeon','dog','ghost');
        $resolved = $all[array_rand($all)];
    } else {
        $resolved = $model_option;
    }
    return $resolved;
}

register_activation_hook(__FILE__, function () {
    if (!get_option('sgn_options')) add_option('sgn_options', sgn_defaults());
});

// ==========================================
// 2. 后台设置
// ==========================================
class SGN_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page() {
        add_options_page('Shiguang Neko 设置', '时光宠物', 'manage_options', 'shiguang-neko', array($this, 'create_admin_page'));
    }

    public function create_admin_page() {
        $is_saved = isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true';
        ?>
        <style>
            div.updated, div.error, div.notice { display: none !important; }
            :root { --sgn-bg: #f5f5f7; --sgn-sidebar: rgba(255, 255, 255, 0.7); --sgn-card: #ffffff; --sgn-accent: #007aff; --sgn-text: #1d1d1f; --sgn-border: #d2d2d7; }
            .sgn-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px 20px 0 0; color: var(--sgn-text); }
            .sgn-layout { display: flex; min-height: 680px; background: var(--sgn-bg); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid rgba(0,0,0,0.05); margin-top: 20px; }
            .sgn-sidebar { width: 240px; background: var(--sgn-sidebar); backdrop-filter: blur(20px); border-right: 1px solid rgba(0,0,0,0.06); padding: 30px 15px; flex-shrink: 0; }
            .sgn-logo-area { padding: 0 10px 30px; border-bottom: 1px solid rgba(0,0,0,0.06); margin-bottom: 15px; }
            .sgn-logo-area h1 { font-size: 20px; font-weight: 700; margin: 0; }
            .sgn-logo-area span { font-size: 11px; color: #888; background: #e5e5ea; padding: 2px 6px; border-radius: 4px; margin-left: 6px; }
            .sgn-nav-item { display: flex; align-items: center; padding: 10px 14px; margin-bottom: 4px; border-radius: 8px; cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 13px; color: #444; }
            .sgn-nav-item:hover { background: rgba(0,0,0,0.04); }
            .sgn-nav-item.active { background: var(--sgn-accent); color: #fff; }
            .sgn-nav-icon { margin-right: 10px; font-size: 15px; }
            .sgn-content { flex-grow: 1; padding: 40px 50px; overflow-y: auto; background: var(--sgn-card); }
            .sgn-tab-pane { display: none; animation: sgnFade 0.3s ease; }
            .sgn-tab-pane.active { display: block; }
            @keyframes sgnFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
            .sgn-group-title { font-size: 13px; font-weight: 600; text-transform: uppercase; color: #86868b; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; }
            .form-table th { padding: 20px 10px 20px 0; width: 160px; font-weight: 500; vertical-align: top; }
            .sgn-input, .sgn-textarea { width: 100%; max-width: 450px; padding: 8px 12px; border: 1px solid var(--sgn-border); border-radius: 8px; font-size: 14px; background: #fff; transition: 0.2s; }
            .sgn-input:focus { border-color: var(--sgn-accent); outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.1); }
            .sgn-desc { display: block; margin-top: 6px; color: #86868b; font-size: 12px; line-height: 1.5; }
            .sgn-switch { position: relative; display: inline-block; width: 40px; height: 24px; vertical-align: middle; margin-right: 10px; }
            .sgn-switch input { opacity: 0; width: 0; height: 0; }
            .sgn-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e9e9ea; transition: .3s; border-radius: 24px; }
            .sgn-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 2px; bottom: 2px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            input:checked + .sgn-slider { background-color: #34c759; }
            input:checked + .sgn-slider:before { transform: translateX(16px); }
            .sgn-footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #f0f0f0; text-align: right; }
            .button-primary.sgn-btn { background: var(--sgn-accent); border: none; border-radius: 8px; padding: 8px 24px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,122,255,0.25); }
            #sgn-toast { position: fixed; top: 40px; left: 50%; transform: translateX(-50%) translateY(-100px); background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(0,0,0,0.1); color: #333; padding: 10px 24px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 13px; z-index: 100000; opacity: 0; transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1); }
            #sgn-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
            #sgn-toast svg { width: 18px; height: 18px; fill: #34c759; }
        </style>

        <div class="wrap sgn-wrap">
            <form method="post" action="options.php">
                <?php settings_fields('sgn_option_group'); ?>
                <div class="sgn-layout">
                    <div class="sgn-sidebar">
                        <div class="sgn-logo-area"><h1>Neko <span class="version">v<?php echo SGN_VERSION; ?></span></h1></div>
                        <div class="sgn-nav-item active" data-tab="tab-main"><span class="sgn-nav-icon">🎨</span> 外观</div>
                        <div class="sgn-nav-item" data-tab="tab-features"><span class="sgn-nav-icon">⚡️</span> 功能</div>
                        <div class="sgn-nav-item" data-tab="tab-tune"><span class="sgn-nav-icon">🎛️</span> 调参</div>
                        <div class="sgn-nav-item" data-tab="tab-quotes"><span class="sgn-nav-icon">💬</span> 语录 & 文本</div>
                        <div style="margin-top:auto"></div>
                        <div class="sgn-nav-item" data-tab="tab-about"><span class="sgn-nav-icon">ℹ️</span> 关于</div>
                    </div>
                    <div class="sgn-content">
                        <div id="tab-main" class="sgn-tab-pane active">
                            <div class="sgn-group-title">Appearance</div>
                            <table class="form-table" role="presentation"><?php do_settings_fields('shiguang-neko', 'sgn_main'); ?></table>
                        </div>
                        <div id="tab-features" class="sgn-tab-pane">
                            <div class="sgn-group-title">Interactions</div>
                            <table class="form-table" role="presentation"><?php do_settings_fields('shiguang-neko', 'sgn_features'); ?></table>
                        </div>
                        <div id="tab-tune" class="sgn-tab-pane">
                            <div class="sgn-group-title">Probabilities</div>
                            <table class="form-table" role="presentation"><?php do_settings_fields('shiguang-neko', 'sgn_tune'); ?></table>
                        </div>
                        <div id="tab-quotes" class="sgn-tab-pane">
                            <div class="sgn-group-title">Text & Messages</div>
                            <table class="form-table" role="presentation"><?php do_settings_fields('shiguang-neko', 'sgn_quotes'); ?></table>
                        </div>
                        <div id="tab-about" class="sgn-tab-pane">
                            <div style="padding: 20px; background: #f9f9f9; border-radius: 12px; color: #666;">
                                <h3 style="margin-top:0; color:#333;">Shiguang Neko</h3>
                                <p>修复了移动端喂食悬空、进食错位及标题恢复等问题。</p>
                                <p>当前版本: <?php echo SGN_VERSION; ?></p>
                            </div>
                        </div>
                        <div class="sgn-footer"><?php submit_button('保存所有设置', 'primary sgn-btn'); ?></div>
                    </div>
                </div>
            </form>
        </div>
        <div id="sgn-toast"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><span>设置已成功保存</span></div>
        <script>
            jQuery(document).ready(function($){
                $('.sgn-nav-item').click(function(){
                    $('.sgn-nav-item').removeClass('active'); $(this).addClass('active');
                    var target = $(this).data('tab');
                    $('.sgn-tab-pane').hide().removeClass('active'); $('#' + target).fadeIn(200).addClass('active');
                });
                <?php if ($is_saved) : ?>
                setTimeout(function(){ $('#sgn-toast').addClass('show'); setTimeout(function(){ $('#sgn-toast').removeClass('show'); }, 3000); }, 500);
                <?php endif; ?>
            });
        </script>
        <?php
    }

    public function page_init() {
        register_setting('sgn_option_group', 'sgn_options', array($this, 'sanitize'));
        add_settings_section('sgn_main', '', null, 'shiguang-neko');
        add_settings_section('sgn_features', '', null, 'shiguang-neko');
        add_settings_section('sgn_tune', '', null, 'shiguang-neko');
        add_settings_section('sgn_quotes', '', null, 'shiguang-neko');

        // Fields
        add_settings_field('model_type', '宠物形象', array($this, 'field_model_type'), 'shiguang-neko', 'sgn_main');
        add_settings_field('mobile_display', '移动端', array($this, 'switch_mobile_display'), 'shiguang-neko', 'sgn_main');
        add_settings_field('api_url', '语录 API', array($this, 'field_api_url'), 'shiguang-neko', 'sgn_main');

        add_settings_field('enable_drag', '允许拖拽', array($this, 'switch_enable_drag'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_sleep', '睡眠模式', array($this, 'switch_enable_sleep'), 'shiguang-neko', 'sgn_features');
        add_settings_field('sleep_time', '作息时间', array($this, 'field_sleep_time'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_scroll', '滚动失重', array($this, 'switch_enable_scroll'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_visibility', '离屏互动', array($this, 'switch_enable_visibility'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_reading', '鼠标伴读', array($this, 'switch_enable_reading'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_toy', '投掷喂食', array($this, 'switch_enable_toy'), 'shiguang-neko', 'sgn_features');
        add_settings_field('enable_attack', '抓挠特效', array($this, 'switch_enable_attack'), 'shiguang-neko', 'sgn_features');

        add_settings_field('action_chance', '随机动作概率', array($this, 'field_action_chance'), 'shiguang-neko', 'sgn_tune');
        add_settings_field('talk_chance', '主动说话概率', array($this, 'field_talk_chance'), 'shiguang-neko', 'sgn_tune');
        add_settings_field('reading_chance', '伴读触发概率', array($this, 'field_reading_chance'), 'shiguang-neko', 'sgn_tune');
        add_settings_field('toy_chance', '喂食掉落概率', array($this, 'field_toy_chance'), 'shiguang-neko', 'sgn_tune');
        add_settings_field('attack_chance', '攻击/抓挠概率', array($this, 'field_attack_chance'), 'shiguang-neko', 'sgn_tune');
        add_settings_field('idle_sleep_ms', '闲置入睡时间', array($this, 'field_idle_sleep_ms'), 'shiguang-neko', 'sgn_tune');

        add_settings_field('text_visibility', '离屏/回来 标题', array($this, 'field_text_visibility'), 'shiguang-neko', 'sgn_quotes');
        add_settings_field('msg_back', '回来时说的话', array($this, 'field_msg_back'), 'shiguang-neko', 'sgn_quotes');
        add_settings_field('text_greetings', '早/午/晚 问候', array($this, 'field_text_greetings'), 'shiguang-neko', 'sgn_quotes');
        
        add_settings_field('cat_sentences', '🐈 黑猫随机语录', array($this, 'field_cat_sentences'), 'shiguang-neko', 'sgn_quotes');
        add_settings_field('bird_sentences', '🐦 鸽子随机语录', array($this, 'field_bird_sentences'), 'shiguang-neko', 'sgn_quotes');
        add_settings_field('dog_sentences', '🐶 柯基随机语录', array($this, 'field_dog_sentences'), 'shiguang-neko', 'sgn_quotes');
        add_settings_field('ghost_sentences', '👻 幽灵随机语录', array($this, 'field_ghost_sentences'), 'shiguang-neko', 'sgn_quotes');
    }

    public function sanitize($input) {
        $d = sgn_defaults();
        $out = array();
        $out['model_type'] = sanitize_text_field($input['model_type'] ?? $d['model_type']);
        $out['mobile_display'] = isset($input['mobile_display']) ? '1' : '0';
        $out['api_url'] = esc_url_raw(trim($input['api_url'] ?? ''));
        foreach (array('enable_drag','enable_sleep','enable_scroll','enable_visibility','enable_reading','enable_toy','enable_attack') as $k) {
            $out[$k] = isset($input[$k]) ? '1' : '0';
        }
        $out['sleep_start'] = strval($this->clamp_int($input['sleep_start'] ?? 23, 0, 23));
        $out['sleep_end'] = strval($this->clamp_int($input['sleep_end'] ?? 6, 0, 23));
        foreach (array('action_chance','talk_chance','reading_chance','toy_chance','attack_chance') as $k) {
            $out[$k] = strval($this->clamp_int($input[$k] ?? $d[$k], 0, 100));
        }
        $out['idle_sleep_ms'] = strval(max(30000, min(3600000, intval($input['idle_sleep_ms'] ?? 300000))));
        $text_fields = array('title_lost','title_back','msg_back','greet_morning','greet_noon','greet_night');
        foreach ($text_fields as $k) { $out[$k] = sanitize_text_field($input[$k] ?? $d[$k]); }
        foreach (array('cat_sentences','bird_sentences','dog_sentences','ghost_sentences') as $k) {
            $out[$k] = isset($input[$k]) ? wp_kses_post($input[$k]) : $d[$k];
        }
        return $out;
    }

    private function clamp_int($v, $min, $max) { return max($min, min($max, intval($v))); }
    private function opt($key) { $o = sgn_get_options(); return isset($o[$key]) ? $o[$key] : ''; }
    private function switch_field($key, $desc) {
        $val = $this->opt($key);
        echo '<label class="sgn-switch"><input type="checkbox" name="sgn_options[' . esc_attr($key) . ']" value="1" ' . checked('1', $val, false) . '><span class="sgn-slider"></span></label>';
        echo '<span class="sgn-desc">' . esc_html($desc) . '</span>';
    }
    private function textarea($key) { echo "<textarea name='sgn_options[" . esc_attr($key) . "]' class='sgn-textarea' rows='5'>" . esc_textarea($this->opt($key)) . "</textarea>"; }
    private function chance_field($key, $desc = '') {
        $val = $this->opt($key);
        echo "<input type='number' name='sgn_options[" . esc_attr($key) . "]' value='" . esc_attr($val) . "' class='sgn-input' style='width:80px;' min='0' max='100' /> %";
        if($desc) echo "<span class='sgn-desc'>$desc</span>";
    }

    public function field_model_type() {
        $cur = $this->opt('model_type');
        $models = array('cat'=>'🐈 黑猫', 'pigeon'=>'🐦 鸽子', 'dog'=>'🐶 柯基', 'ghost'=>'👻 幽灵', 'random'=>'🎲 随机');
        foreach ($models as $val => $label) { echo '<label style="margin-right: 15px; cursor:pointer;"><input type="radio" name="sgn_options[model_type]" value="' . $val . '" ' . checked($val, $cur, false) . '> ' . $label . '</label>'; }
    }
    public function field_api_url() { echo "<input type='text' name='sgn_options[api_url]' value='" . esc_attr($this->opt('api_url')) . "' class='sgn-input' placeholder='https://...' />"; echo "<span class='sgn-desc'>API 返回格式需兼容一言 JSON。</span>"; }
    public function switch_mobile_display() { $this->switch_field('mobile_display', '在窄屏设备上隐藏宠物。'); }
    public function switch_enable_drag() { $this->switch_field('enable_drag', '允许拖拽，松手有物理掉落效果。'); }
    public function switch_enable_sleep() { $this->switch_field('enable_sleep', '到达设定时间或长时间无操作自动休眠。'); }
    public function field_sleep_time() {
        $s = $this->opt('sleep_start'); $e = $this->opt('sleep_end');
        echo '入睡: <input type="number" name="sgn_options[sleep_start]" value="'.$s.'" class="sgn-input" style="width:60px" min="0" max="23"> 点 &nbsp;&nbsp;';
        echo '醒来: <input type="number" name="sgn_options[sleep_end]" value="'.$e.'" class="sgn-input" style="width:60px" min="0" max="23"> 点';
        echo '<span class="sgn-desc">24小时制。例如：23 和 6 表示晚上11点睡，早上6点醒。</span>';
    }
    public function switch_enable_scroll() { $this->switch_field('enable_scroll', '快速滚动页面时产生失重效果。'); }
    public function switch_enable_visibility() { $this->switch_field('enable_visibility', '切换标签页时修改标题及互动。'); }
    public function switch_enable_reading() { $this->switch_field('enable_reading', '鼠标悬停在文本上时触发伴读。'); }
    public function switch_enable_toy() { $this->switch_field('enable_toy', '点击页面空白处掉落食物/玩具。'); }
    public function switch_enable_attack() { $this->switch_field('enable_attack', '点击宠物有概率触发抓挠碎屏。'); }
    public function field_action_chance() { $this->chance_field('action_chance', '闲置状态下，宠物随机做动作的频率。'); }
    public function field_talk_chance() { $this->chance_field('talk_chance', '闲置状态下，宠物主动说话的频率。'); }
    public function field_reading_chance() { $this->chance_field('reading_chance', '鼠标划过文字时，触发伴读气泡的概率。'); }
    public function field_toy_chance() { $this->chance_field('toy_chance', '点击空白处时，掉落食物/玩具的概率。'); }
    public function field_attack_chance() { $this->chance_field('attack_chance', '点击宠物身体时，触发攻击特效的概率。'); }
    public function field_idle_sleep_ms() {
        $val = intval($this->opt('idle_sleep_ms'));
        $min = round($val / 60000, 1);
        echo "<input type='number' step='1000' name='sgn_options[idle_sleep_ms]' value='" . esc_attr($val) . "' class='sgn-input' style='width:120px;' /> ms";
        echo "<span class='sgn-desc'>当前设置约等于 <strong>{$min} 分钟</strong>。</span>";
    }
    public function field_text_visibility() {
        echo '<p>失去焦点: <input type="text" name="sgn_options[title_lost]" value="'.esc_attr($this->opt('title_lost')).'" class="sgn-input" style="width:250px"></p>';
        echo '<p>重新获得: <input type="text" name="sgn_options[title_back]" value="'.esc_attr($this->opt('title_back')).'" class="sgn-input" style="width:250px"></p>';
        echo '<span class="sgn-desc">浏览器标签页标题显示的文字。</span>';
    }
    public function field_msg_back() {
        echo '<input type="text" name="sgn_options[msg_back]" value="'.esc_attr($this->opt('msg_back')).'" class="sgn-input" style="width:100%">';
        echo '<span class="sgn-desc">用户回到页面时，宠物说的话。留空则不说话。</span>';
    }
    public function field_text_greetings() {
        echo '<p>早安 (6-11): <input type="text" name="sgn_options[greet_morning]" value="'.esc_attr($this->opt('greet_morning')).'" class="sgn-input" style="width:300px"></p>';
        echo '<p>午安 (11-14): <input type="text" name="sgn_options[greet_noon]" value="'.esc_attr($this->opt('greet_noon')).'" class="sgn-input" style="width:300px"></p>';
        echo '<p>晚安 (20-23): <input type="text" name="sgn_options[greet_night]" value="'.esc_attr($this->opt('greet_night')).'" class="sgn-input" style="width:300px"></p>';
    }
    public function field_cat_sentences() { $this->textarea('cat_sentences'); }
    public function field_bird_sentences() { $this->textarea('bird_sentences'); }
    public function field_dog_sentences() { $this->textarea('dog_sentences'); }
    public function field_ghost_sentences() { $this->textarea('ghost_sentences'); }
}

if (is_admin()) new SGN_Settings();

// 前台资源
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('jquery');
    $base = plugin_dir_url(__FILE__);
    wp_enqueue_style('sgn-style', $base . 'assets/css/neko.css', array(), SGN_VERSION);
    wp_enqueue_script('sgn-script', $base . 'assets/js/neko.js', array('jquery'), SGN_VERSION, true);

    $opt = sgn_get_options();
    $model = sgn_resolve_model($opt);
    $key = ($model === 'pigeon') ? 'bird_sentences' : $model . '_sentences';
    $sentences = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', (string)($opt[$key] ?? '')))));
    if (empty($sentences)) $sentences = array('...');

    $cfg = array(
        'model' => $model,
        'apiUrl' => $opt['api_url'],
        'sentences' => $sentences,
        'features' => array(
            'enableDrag' => ($opt['enable_drag'] === '1'),
            'enableSleep' => ($opt['enable_sleep'] === '1'),
            'enableScroll' => ($opt['enable_scroll'] === '1'),
            'enableVisibility' => ($opt['enable_visibility'] === '1'),
            'enableReading' => ($opt['enable_reading'] === '1'),
            'enableToy' => ($opt['enable_toy'] === '1'),
            'enableAttack' => ($opt['enable_attack'] === '1'),
        ),
        'actionChance' => max(0, min(1, intval($opt['action_chance']) / 100.0)),
        'talkChance' => max(0, min(1, intval($opt['talk_chance']) / 100.0)),
        'readingChance' => max(0, min(1, intval($opt['reading_chance']) / 100.0)),
        'toyChance' => max(0, min(1, intval($opt['toy_chance']) / 100.0)),
        'attackChance' => max(0, min(1, intval($opt['attack_chance']) / 100.0)),
        'idleSleepMs' => max(30000, min(3600000, intval($opt['idle_sleep_ms']))),
        'scrollThreshold' => 60,
        'sleepStart' => intval($opt['sleep_start']),
        'sleepEnd' => intval($opt['sleep_end']),
        'texts' => array(
            'titleLost' => $opt['title_lost'],
            'titleBack' => $opt['title_back'],
            'msgBack' => $opt['msg_back'],
            'morning' => $opt['greet_morning'],
            'noon' => $opt['greet_noon'],
            'night' => $opt['greet_night']
        ),
        'labels' => array('toy' => array('cat' => '毛线团', 'pigeon' => '薯条', 'dog' => '肉骨头', 'ghost' => '魂玉'))
    );
    wp_add_inline_script('sgn-script', 'window.SGN_CFG=' . wp_json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';', 'before');
}, 20);

// 前台渲染
add_action('wp_footer', function () {
    $opt = sgn_get_options();
    $model = sgn_resolve_model($opt);
    $hideMobile = isset($opt['mobile_display']) && $opt['mobile_display'] === '1';
    ?>
    <div id="sgn-pet-root" class="sgn-model-<?php echo esc_attr($model); ?><?php echo $hideMobile ? ' sgn-hide-mobile' : ''; ?>">
        <div id="sgn-pet-msg" aria-live="polite">...</div>
        <div id="sgn-pet-sleep">Zzz...</div>
        <div id="sgn-pet-flipper"><div id="sgn-pet-body"><?php echo sgn_get_svg($model); ?></div></div>
    </div>
    <div id="sgn-toy-layer"><div id="sgn-toy-item" class="sgn-toy"><?php echo sgn_get_toy_svg($model); ?></div></div>
    <div id="sgn-effect-layer"></div>
    <?php
});

function sgn_get_svg($model) {
    if ($model === 'pigeon') return '<svg viewBox="0 0 32 32" class="sgn-pixel-svg"><path d="M10 16 h14 v10 h-14 Z" fill="#D3D3D3"/><path d="M12 18 h10 v6 h-10 Z" fill="#A9A9A9"/><rect x="14" y="20" width="6" height="1" fill="#808080"/><rect x="14" y="22" width="6" height="1" fill="#808080"/><path d="M6 18 l4 4 v-6 Z" fill="#696969"/><g class="sgn-head"><rect x="20" y="8" width="8" height="8" fill="#D3D3D3"/><rect x="22" y="10" width="1" height="1" fill="#000"/><rect x="24" y="10" width="1" height="1" fill="#000"/><rect x="28" y="11" width="2" height="1" fill="#FFA500"/><rect x="20" y="15" width="8" height="1" fill="#556B2F"/></g><rect x="14" y="26" width="2" height="4" fill="#FFA500"/><rect x="20" y="26" width="2" height="4" fill="#FFA500"/></svg>';
    if ($model === 'dog') return '<svg viewBox="0 0 32 32" class="sgn-pixel-svg"><g class="sgn-tail" style="transform-origin: 28px 20px;"><rect x="28" y="18" width="3" height="2" fill="#D08A2D"/><rect x="30" y="17" width="1" height="4" fill="#B06F24"/></g><rect x="10" y="16" width="16" height="10" rx="2" fill="#D08A2D"/><rect x="12" y="19" width="10" height="5" rx="1" fill="#F5F5F5"/><rect x="10" y="26" width="4" height="4" fill="#B06F24"/><rect x="22" y="26" width="4" height="4" fill="#B06F24"/><rect x="3" y="14" width="9" height="9" rx="2" fill="#D08A2D"/><rect x="3" y="22" width="9" height="2" fill="#B06F24"/><rect x="4" y="12" width="3" height="3" fill="#B06F24"/><rect x="9" y="12" width="3" height="3" fill="#B06F24"/><rect x="4" y="18" width="1" height="1" fill="#000"/><rect x="7" y="17" width="1" height="1" fill="#000"/><rect x="9" y="18" width="1" height="1" fill="#000"/><rect x="3" y="19" width="2" height="2" fill="#000"/><rect x="5" y="21" width="2" height="1" fill="#FF69B4"/><rect x="18" y="24" width="6" height="1" fill="#B06F24"/></svg>';
    if ($model === 'ghost') return '<svg viewBox="0 0 32 32" class="sgn-pixel-svg"><path d="M8 10 Q8 2 16 2 Q24 2 24 10 V24 L21 21 L18 24 L16 21 L13 24 L10 21 L8 24 Z" fill="#FFFFFF"/><rect x="11" y="10" width="3" height="3" fill="#000"/><rect x="18" y="10" width="3" height="3" fill="#000"/><rect x="10" y="14" width="2" height="1" fill="#FFC0CB" opacity="0.6"/><rect x="20" y="14" width="2" height="1" fill="#FFC0CB" opacity="0.6"/></svg>';
    return '<svg viewBox="0 0 32 32" class="sgn-pixel-svg"><g class="sgn-tail" style="transform-origin: 26px 22px;"><rect x="26" y="20" width="2" height="6" fill="#000"/><rect x="28" y="18" width="2" height="4" fill="#000"/></g><rect x="6" y="14" width="20" height="14" rx="2" fill="#0E0E0E"/><rect x="6" y="28" width="4" height="2" fill="#000"/><rect x="22" y="28" width="4" height="2" fill="#000"/><rect x="4" y="8" width="16" height="12" rx="1" fill="#000"/><path d="M4 8 L4 4 L8 8 Z" fill="#000"/><path d="M20 8 L20 4 L16 8 Z" fill="#000"/><g class="sgn-eyes" style="transform-origin: 12px 14px;"><rect x="7" y="12" width="2" height="2" fill="#FFD700"/><rect x="15" y="12" width="2" height="2" fill="#FFD700"/></g><rect x="11" y="15" width="2" height="1" fill="#FF69B4"/></svg>';
}

function sgn_get_toy_svg($model) {
    if ($model === 'dog') return '<svg viewBox="0 0 32 32" class="sgn-toy-svg"><rect x="10" y="12" width="12" height="8" rx="2" fill="#F2F2F2"/><rect x="8" y="13" width="3" height="6" rx="2" fill="#F2F2F2"/><rect x="21" y="13" width="3" height="6" rx="2" fill="#F2F2F2"/><rect x="7" y="14" width="2" height="4" fill="#E0E0E0"/><rect x="24" y="14" width="2" height="4" fill="#E0E0E0"/><rect x="12" y="14" width="8" height="1" fill="#D0D0D0"/><rect x="12" y="17" width="8" height="1" fill="#D0D0D0"/></svg>';
    if ($model === 'pigeon') return '<svg viewBox="0 0 32 32" class="sgn-toy-svg"><path d="M8 16 Q8 10 16 10 Q24 10 24 16 V22 Q24 24 22 24 H10 Q8 24 8 22 Z" fill="#F5C16C"/><rect x="10" y="16" width="12" height="1" fill="#E2A84A"/><rect x="11" y="18" width="10" height="1" fill="#E2A84A"/><rect x="12" y="20" width="8" height="1" fill="#E2A84A"/><rect x="13" y="22" width="6" height="1" fill="#E2A84A"/><rect x="12" y="14" width="2" height="1" fill="#8B5A2B" opacity="0.6"/><rect x="18" y="14" width="2" height="1" fill="#8B5A2B" opacity="0.6"/></svg>';
    if ($model === 'ghost') return '<svg viewBox="0 0 32 32" class="sgn-toy-svg"><rect x="12" y="10" width="8" height="12" rx="2" fill="#C084FC"/><rect x="13" y="11" width="6" height="10" rx="2" fill="#E9D5FF" opacity="0.75"/><rect x="14" y="14" width="2" height="2" fill="#000"/><rect x="18" y="14" width="2" height="2" fill="#000"/><rect x="16" y="17" width="2" height="1" fill="#000"/></svg>';
    return '<svg viewBox="0 0 32 32" class="sgn-toy-svg"><circle cx="16" cy="16" r="10" fill="#E53935"/><path d="M10 14 C12 12, 20 12, 22 14" stroke="#FFF" stroke-width="2" fill="none"/><path d="M9 18 C12 16, 20 16, 23 18" stroke="#FFF" stroke-width="2" fill="none"/><path d="M13 9 C14 12, 14 20, 13 23" stroke="#FFF" stroke-width="2" fill="none"/><path d="M19 9 C18 12, 18 20, 19 23" stroke="#FFF" stroke-width="2" fill="none"/><path d="M6 24 C10 23, 12 22, 14 21" stroke="#FFF" stroke-width="2" fill="none"/></svg>';
}
