# 移动端修复报告（虚拟城市前端）

**日期**：2026-10-10 · **性质**：**只改前端** · 未部署 · **未经真机验证**（如实，见 §五）
**起因**：用户报「我在手机上打开虚拟城市，整个是乱的，根本看不到地图」
**受检对象**：`https://www.faceabc.com/city/sh.html`（**与本地 `deploy_city\` 逐字节相同**，见 §一）

---

## 〇、根因（一句话）

> **布局是"横向 flex（地图 + 固定宽侧栏）"，而三个媒体查询全在 900/820/1100px 以上、没有手机断点** ⇒
> **375px 手机上侧栏（300px）占满宽度，地图被挤到 74px；320px 上只剩 19px ⇒ 地图等于看不见**；
> 再叠加 `#person{right:372px;width:400px}`（整块跑到屏外）、`#today-news{width:392px}`（横向溢出）、
> `#app{height:100vh}`（移动端地址栏裁切底部）。

---

## 一、证据（**先复现定位，全部实测**）

### 1.1 线上与本地一致（所以改本地=改线上那份）

用 `curl.exe` 取**原始字节**（⚠ 不能用 `Invoke-WebRequest.Content`：PS 5.1 会按文本重编码，字节数不可信 —— 交接 §六·A·④ 记过这个坑，我第一次就踩了）：

| 文件 | 线上 sha256[:16] | 线上字节 | 本地 sha256[:16] | 结论 |
|---|---|---|---|---|
| `sh.html` | **`2132CE1EEC33F551`** | 11,111 | **`2132CE1EEC33F551`** | ✅ **逐字节相同** |
| `css/city.css` | **`F3140D0B6E46D3C2`** | 19,606 | **`F3140D0B6E46D3C2`** | ✅ **逐字节相同** |
| `index.html` | `2A1E3E050D355B96` | 11,873 | `2A1E3E050D355B96` | ✅ 相同 |

### 1.2 CSS 层的硬证据（`css\city.css` 行号）

| 行 | 规则 | 为什么致命 |
|---|---|---|
| `L10` | `#app{display:flex;flex-direction:column;height:100vh;}` | `100vh` 在移动端 **>** 真实可视高（地址栏）⇒ 底部被裁 |
| `L132` | `#main{flex:1;display:flex;min-height:0;}` | **横向** flex（默认 `row`）⇒ 地图与侧栏并排 |
| `L150` | `#side{width:360px;…}` | 定宽侧栏 |
| `L253` | `@media(max-width:1100px){#side{width:300px;}}` | ⚠ **唯一**碰侧栏的断点，仍是 300px ⇒ **375px 屏上地图 = 375−300−1 = 74px** |
| `L183` | `#today-news{…;width:392px;…}` | **定宽 392px** ⇒ 320/375px 上**横向溢出** |
| `L235` | `#person{position:fixed;right:372px;top:92px;width:400px;}` | **320–430px 屏上整块在屏幕左侧之外** |
| — | 媒体查询总数 **3**（900 / 820 / 1100px） | **没有任何手机断点**；`min-height:44px`、`100dvh`、`touch-action`、`env(safe-area-inset-*)` **均为 0 处** |

### 1.3 布局算术（改前 vs 改后 · 可复算）

（`_mobile_layout_calc.cjs`：按 CSS 语义算"地图可用宽度"与"定宽元素是否溢出"）

| 视口 | **改前**地图宽 | **改后**地图宽 | 改前横向溢出 |
|---|---|---|---|
| 320px | **19px** ⚠ | **320px**（100%）| `#today-news(392px)` + `#person(right:0+w400)` |
| 375px | **74px** ⚠ | **375px**（100%）| 同上 |
| 430px | **129px** ⚠ | **430px**（100%）| — |
| 768px（平板） | 467px | **768px** | — |
| 1280px（桌面） | 919px | **919px** | — ⇒ **桌面不变** ✅ |

### 1.4 画布会跟随（关键前提，已查实）

`js/client.min.js` 的**渲染循环每帧**做：

```js
d.width===d.clientWidth && d.height===d.clientHeight || (d.width=d.clientWidth, d.height=d.clientHeight, …, H(), I=!0)
window.addEventListener("resize",()=>{I=!0;P=!0})
```

⇒ 只要 `#mapwrap` 的真实尺寸变了，**画布会自动重设尺寸并重算比例 `H()`**；
而 `sh.html` 的 `layout()` 本来就在重排后 `dispatchEvent(new Event('resize'))` ⇒ **链路闭合**。

---

## 二、改了哪些文件（**只改前端**）

| 文件 | 改前 sha16 | **改后 sha16** | 大小 | 说明 |
|---|---|---|---|---|
| `deploy_city\css\city.css` | `F3140D0B6E46D3C2` | ⭐ **`75458D32D9DEC712`** | 19,606 → **25,464 B** | **只追加**移动端段落（既有规则一行未改） |
| `deploy_city\sh.html` | `2132CE1EEC33F551` | ⭐ **`F2CE7B16441CA651`** | 11,111 → **13,974 B** | viewport-fit · 抽屉把手 · `wireMobile()` · CSS 版本号 |
| `deploy_city\board.html` | `401C94718C4FED83` | ⭐ **`0E74921666F4C755`** | 1,655 → **1,675 B** | viewport-fit + CSS 版本号（它共用 `city.css`）|
| `deploy_city\mobile-preview.html` | （无） | **`9675 B`** 新增 | — | ⭐ **离线验收页**（见 §四）|
| `deploy_city\index.html` | `2A1E3E050D355B96` | `2A1E3E050D355B96` | 未变 | 无需改（无地图、无外链 css）|

✅ **未改动**（复核通过）：`js\citysim.js` `B45CD471D4037BAD` · `js\citydata_sh.js` `B9850C8E6EACA170` ·
`tools\citydata_sh.mjs` `B2E1EC05C9B4DCC2` · `city_server.js` `E9FB5607CC6FEBC7` ·
`deploy_city\js\client.min.js` `A6A0AEB84AE81432` · `focus.js` `11C7E715761F71D1` · `board.min.js` `0DF191FE8F4F76C6`。

### 2.1 具体改了什么

**CSS（全部在文件末尾追加，含 7 个媒体查询）**
1. `@media(max-width:900px)`：`#app` 加 `100dvh`；`#main` 改 **纵向**；`#side` 去掉定宽、改为底部面板、内部滚动 `max-height:34vh`；`#today-news` 改 `left/right:8px;width:auto`；`#person` 改**底部浮层**（`left:0;right:0;bottom:0;max-height:64vh`）；折叠态 `html.side-collapsed` 隐藏面板内容；触控目标 `min-height:44px`。
2. `@media(max-width:480px)`：再收一档（缩字号/内边距、输入框弹性、面板 `max-height:30vh`）。
3. `@supports(padding:env(safe-area-inset-bottom))`：底部条加安全区内边距。
4. `@media(hover:none)`：去掉触屏上的 hover 粘滞。
5. `#btn-side-toggle{display:none;}`：**桌面端默认隐藏**抽屉把手（不回归）。

**HTML（`sh.html`）**
- `viewport` 加 `viewport-fit=cover`（让 `env(safe-area-inset-*)` 生效）；
- `#topbar` 内新增抽屉把手 `<button id="btn-side-toggle">`；
- `wireMobile()`：抽屉折叠/展开（默认窄屏折叠）· `#person` 出现时给 `<html>` 加 `has-person` · 监听 `visualViewport` 与 `pageshow`。
  ⚠ **抽屉把手放在 `#topbar` 内、不放在地图上** —— 因为地图右上角已被
  `#btn-reset-view(top:10px;right:10px)` 与 `#btn-dotmode(right:96px)` 占满，375px 宽时两者共约 200px
  ⇒ **放地图上必然重叠**（我第一版就是放右侧 50%，自查时发现会盖住它们，已改）。
- CSS 版本号 `?v=202610082000` → **`?v=202610100425`**（防浏览器用旧缓存样式）。

**`board.html`**：同样加 `viewport-fit=cover` + CSS 版本号（它引用同一个 `city.css`）。

---

## 三、验证（**静态 + 算术，共 36 项全通过**）

| 校验 | 结果 |
|---|---|
| CSS 花括号配平 | ✅ 271 vs 271 |
| 7 个 `@media` 块可配平 | ✅ 异常 0 |
| 关键修复落地（8 项：纵向堆叠 / 事件条去定宽 / 档案底部浮层 / 折叠隐藏 / 把手 44×44 / 按钮 44px / dvh / 桌面隐藏把手）| ✅ 8/8 |
| HTML：viewport·版本号·把手元素·`wireMobile()` 两处调用·`visualViewport`·仍派发 `resize` | ✅ 14/14 |
| 内联脚本语法解析（`vm.Script` 编译，不执行）+ `<div>` 配平 | ✅ 4 文件 8/8（`sh.html` 4 段脚本全通过）|
| 布局算术（§1.3）| ✅ 320/375/430 溢出消除；桌面 919px **不变** |

复现脚本（`<本地数据目录>/`）：`_mobile_verify.cjs`（28 项）· `_mobile_layout_calc.cjs`（算术）· `_html_script_check.cjs`（8 项）。

---

## 四、你怎么验收（**手机即可，两条路**）

### 路线 A（推荐，最快）：打开离线预览页
把 `civilization\deploy_city\mobile-preview.html` **连同同目录的 `css\city.css`** 拷到手机（或电脑上用手机尺寸的浏览器），打开：
- 默认应是**地图占满上方、面板在底部一行**（把手显示 `☰`）；
- 点右上角把手 ⇒ 面板收起/展开，**地图高度随之变化**；
- 点「🎲 随机看一位」⇒ 档案应以**底部浮层**出现（不是跑到屏幕左边外）；
- ⚠ 若出现**横向滚动条**或**地图被挤成一条缝** ⇒ 说明没修好，请截图。

### 路线 B：手机看**线上**（需你部署后才生效）
线上现在是旧版（`sh.html 2132CE1E…` / `city.css F3140D0B…`）。部署后按 §六 复核：
1. 手机竖屏打开 `https://www.faceabc.com/city/sh.html`；
2. 地图应可见（不再是一条缝）；无横向滚动；
3. 把手可折叠/展开面板；点「随机看一位」档案从底部弹出。

---

## 五、⚠ 未验证项（**如实，不假装**）

```
❌ **没有真机/浏览器自动化验证**（本机无 Playwright/Puppeteer，且按要求未安装）
   ⇒ 我**没有**在 320/375/430/768px 的真实渲染里看过效果；
     以上全部是【静态解析 + 布局算术 + 语法编译】。
❌ **未验证真机触控**（44px 目标是否好点 · 抽屉是否误触 · 双击缩放行为）
❌ **未验证 iOS Safari 的 100dvh/visualViewport 实际行为**（只按标准写，未实测）
❌ **未验证画布在手机上的实际清晰度**（devicePixelRatio 缩放是既有实现，本次未动）
❌ **未验证** `board.html`（留言板）在手机上的实际效果（只做了 viewport 与版本号）
⚠ 本机有仿真在跑（`tools\_soul_test.cjs`）⇒ 本次**未跑任何重负载**、未清目录
```

---

## 六、回滚

```
备份目录：<本地数据目录>/_edit_backups\mobile_20261010042440\
  sh.html                = 2132CE1EEC33F551 (11,111 B)
  index.html             = 2A1E3E050D355B96 (11,873 B)   ← 本次未改
  board.html             = 401C94718C4FED83 (1,655 B)
  css__city.css          = F3140D0B6E46D3C2 (19,606 B)
  SHA_BEFORE.txt         （4 行指纹清单）

① 全部回滚：
   copy <本地数据目录>/_edit_backups\mobile_20261010042440\sh.html          → deploy_city\sh.html
   copy <本地数据目录>/_edit_backups\mobile_20261010042440\board.html       → deploy_city\board.html
   copy <本地数据目录>/_edit_backups\mobile_20261010042440\css__city.css    → deploy_city\css\city.css
② 只回滚 CSS（保留抽屉）：copy css__city.css → deploy_city\css\city.css
   ⚠ 但此时 sh.html 里的 #btn-side-toggle 不再被样式隐藏 ⇒ 桌面端会多出一个按钮
   ⇒ **要回滚就整组一起回滚**（4 个文件 + preview 可留可删）
③ 回滚后应恢复的指纹：sh.html `2132CE1EEC33F551` · city.css `F3140D0B6E46D3C2`
④ 新增文件可留：deploy_city\mobile-preview.html（删掉不影响线上）
```

---

## 七、部署注意（**由用户决定，本报告不部署**）

```
① 要推的文件：deploy_city\sh.html · css\city.css · board.html（+ 可选 mobile-preview.html）
② ⚠ **sh.html 与 city.css 必须一起推** —— 只推 CSS 会让 sh.html 里的把手无样式（桌面多一个按钮）；
     只推 sh.html 则移动端规则不存在（等于没修）。
③ ⚠ 两个文件都改了 `?v=202610100425` ⇒ 客户端会重新拉 CSS，**不需要额外清缓存**
④ 引擎与数据文件**不要动**（本次完全未碰）
```
