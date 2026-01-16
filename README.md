<div align="center">

# 👾 Shiguang Neko (时光宠物)

[![Version](https://img.shields.io/badge/Version-4.8.0-blue?style=flat-square)](https://github.com/yxs2003/shiguang-neko)
[![WordPress](https://img.shields.io/badge/WordPress-Plugin-21759b?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPLv2-green?style=flat-square)](LICENSE)
[![Author](https://img.shields.io/badge/Author-Shiguang-orange?style=flat-square)](https://www.shiguang.ink/)

**一个轻量级、高度可定制的 WordPress 像素电子宠物插件。**
<br>
为你的网站添加一只会说话、会睡觉、懂物理重力的像素伙伴。

[查看演示](https://www.shiguang.ink/) · [报告 Bug](https://github.com/yxs2003/shiguang-neko/issues) · [功能建议](https://github.com/yxs2003/shiguang-neko/issues)

</div>

---

## ✨ 核心特性 (Features)

Shiguang Neko 不仅仅是一个装饰，它拥有完整的交互逻辑和拟人化行为：

### 🎨 多样化形象
* **多角色支持**：内置 🐈 **黑猫**、🐶 **柯基**、🐦 **鸽子**、👻 **幽灵** 四种像素形象。
* **随机模式**：支持每次刷新页面随机出现不同的宠物。

### 🎮 深度交互系统
* **物理重力拖拽**：鼠标按住宠物可随意拖拽，松手后宠物会模拟重力自然坠落并弹跳。
* **投掷喂食**：点击页面空白处，会掉落对应的食物（如毛线球、骨头），宠物会跑去追逐并进食。
* **情感反馈**：点击宠物身体会触发不同反应（开心跳跃或生气抓挠屏幕特效）。
* **滚动失重**：当页面快速滚动时，宠物会因为“惯性”被甩飞或悬空。

### 🤖 智能 AI 行为
* **作息规律**：支持自定义 **入睡时间** 和 **醒来时间**（默认 23:00 - 06:00）。夜间宠物会自动带上睡帽呼呼大睡。
* **离屏互动**：当用户切换浏览器标签页时，网页标题会自动变为吐槽语（如“人呢？”），切回时会表示欢迎。
* **阅读伴读**：当鼠标悬停在文章段落或链接上时，宠物会瞬移过来“一起看”。
* **随机动作**：闲置时会自动做坐下、旋转、伸懒腰、嗅探等动作。

### 💬 丰富的语录系统
* **一言 API**：支持接入第三方 Hitokoto API，让宠物说出富有哲理或有趣的句子。
* **自定义语录**：后台可完全自定义每个角色的本地语录库。
* **场景对话**：早安/午安/晚安问候、喂食对话、被攻击时的抱怨等均可自定义。

### 🎛️ 现代化后台 (v4.8+)
* **macOS 风格 UI**：全新重构的设置面板，采用毛玻璃特效与卡片式布局。
* **概率微调**：所有行为（说话、动作、伴读、攻击）的触发概率均可 0-100% 自由调节。
* **Toast 通知**：优雅的保存成功提示。

---

## 📸 截图展示 (Screenshots)

宠物睡觉

[![pZrNDu8.jpg](https://s41.ax1x.com/2026/01/15/pZrNDu8.jpg)](https://imgchr.com/i/pZrNDu8)

后台设置
[![pZrNrDS.jpg](https://s41.ax1x.com/2026/01/15/pZrNrDS.jpg)](https://imgchr.com/i/pZrNrDS)

---

## 🚀 安装指南 (Installation)

### 方法 1：直接上传
1. 下载本仓库的 `zip` 压缩包。
2. 进入 WordPress 后台 -> **插件** -> **安装插件** -> **上传插件**。
3. 选择压缩包并安装，最后点击 **启用**。

### 方法 2：FTP 安装
1. 解压下载的文件。
2. 将 `shiguang-neko` 文件夹上传至服务器的 `/wp-content/plugins/` 目录。
3. 进入 WordPress 后台启用插件。

---

## ⚙️ 配置说明 (Configuration)

启用插件后，请前往 **设置 (Settings)** -> **时光宠物 (Shiguang Neko)** 进行配置。

### 1. 外观 (Appearance)
* **宠物形象**：选择你喜欢的角色，或选择“随机”。
* **移动端隐藏**：建议开启，防止在手机屏幕上遮挡内容。
* **API 地址**：默认使用一言 API，也可替换为你自己的 JSON API。

### 2. 功能 (Features)
* **睡眠模式**：设置入睡（如 23点）和醒来（如 6点）时间。期间宠物会变为静止睡眠状态，不再进行耗能交互。
* **离屏互动**：开启后，用户切屏时标题会改变。

### 3. 调参 (Tuning)
* **概率滑块**：你可以控制宠物的“活跃度”。
    * *Example*: 将 `动作概率` 调高，宠物会更爱动；将 `说话概率` 调低，宠物会更安静。

---

## 🛠️ 技术细节 (Tech Stack)

* **Frontend**: Native JavaScript (ES6+), jQuery (WordPress 依赖), CSS3 Animations (Keyframes).
* **Backend**: PHP (WordPress Options API).
* **Design**: SVG Vector Graphics (Pixel Art style), Glassmorphism UI.

---

## 📝 更新日志 (Changelog)

### v4.9.2 更新日志

* **📱 后台全面响应式重构 (Responsive Admin UI)**
* **移动端适配**：重写了设置面板的 CSS，现在在手机和平板上会自动切换为垂直布局，侧边栏菜单自动堆叠或横向排列，操作更顺手。
* **触控优化**：增大了移动端上的按钮和输入框点击区域，防止误触。
* **布局修复**：修复了移动端 WordPress Admin Bar 遮挡保存提示 (Toast) 的问题。


* **🛡️ 代码健壮性升级 (Robustness)**
* **空值防御**：在 JS 中增加了对配置对象 (`SGN_CFG`) 和 DOM 元素的严格空值检查，防止在未加载插件的页面产生控制台报错。
* **数据净化**：加强了 PHP 端对 API URL 和用户输入文本的安全过滤机制。


* **📝 开发者体验优化**
* **全注释覆盖**：核心 PHP 文件与 JS 逻辑文件现已包含详细的中文注释，涵盖物理引擎、AI 行为循环及后台构建逻辑，极大方便二次开发与维护。


* **⚡️ 交互体验微调**
* **滚动冲突修复**：彻底移除了 CSS 中可能导致移动端页面无法滚动的 `touch-action` 限制，仅在拖拽宠物时通过 JS 阻止默认行为。
* **动画优化**：微调了 CSS 动画层级，确保宠物和道具始终位于页面最上层且不被页脚遮挡。

### v4.9.0 更新日志

* **🐛 修复移动端喂食悬空**：修复了安卓设备上因地址栏高度动态变化导致食物悬浮在半空的问题。
* **🍖 优化进食动作交互**：重构了宠物移动逻辑，现在宠物会准确地凑到食物旁边进食，不再出现“用身子吃东西”的错位现象。
* **📝 修复标题恢复逻辑**：解决了用户频繁切换标签页时，网页原始标题被插件提示语覆盖而无法恢复的 Bug。
* **📱 优化移动端触摸体验**：改进了触摸事件判定，解决了安卓机型上点击宠物互动与页面滚动冲突的问题。
* **🎨 界面细节调整**：调整了食物图层的 Z-Index 层级，防止掉落物被页脚或其他元素遮挡。

### v4.8.0
* ✨ **UI 重构**：后台设置面板升级为 macOS Big Sur 风格。
* 🕐 **作息系统**：新增自定义入睡和醒来时间设置。
* 🎲 **概率控制**：开放所有交互行为的触发概率调节。
* 📝 **文本自定义**：支持自定义离屏标题、回来时的欢迎语及早晚安问候。
* 🐛 **修复**：修复了离屏标题可能卡死的问题；优化了 Toast 提示样式。

### v4.5.0 - v4.7.0
* 增加喂食互动（点击空白处掉落食物）。
* 增加抓挠碎屏特效。
* 增加阅读伴读功能。

---

## 🤝 贡献 (Contributing)

欢迎提交 Issue 或 Pull Request！如果你有新的像素素材或有趣的想法，请随时分享。

特别鸣谢：ChatGPT(感谢大兵)，Gemini(感谢学生优惠），API链接https://v1.hitokoto.cn/?c=b

1. Fork 本仓库
2. 新建分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 提交 Pull Request

---

## 📄 开源协议 (License)

本项目基于 [GPL-2.0](LICENSE) 协议开源。

Copyright (c) 2026 Shiguang.
