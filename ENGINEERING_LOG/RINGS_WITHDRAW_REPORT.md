# 「模型的 5 环」撤回报告

**日期**：2026-10-08 14:36
**起因**：用户看过效果后说「**画圈还不如不画呢**」⇒ 撤掉
**结论**：**已撤回 · 三个文件全部回到画环之前的状态（sha256 逐字节验证）**

---

## 一、撤了什么

上一班在地图上叠加的「模型的 5 环」：

| 元素 | 内容 |
|---|---|
| 4 条金色虚线同心圆 | 半径 9.40 / 18.80 / 28.20 / 37.60 km · `rgba(255,210,63,0.38)` |
| 中心 3px 金点 | 环 0 |
| `环 0`~`环 4` 标签 | 东南 130° 斜排 |
| `save/restore` 包裹 | 为防 `setLineDash` 外泄给 `roads` 环线 |

⇒ **整个「画环」插入块已移除** ✓

---

## 二、⭐ 撤回方式：**按原始偏移精确剥离 · 并用备份做硬断言**

不是"重新写一遍"，而是：

```
① 用字符级 diff 定位【全部】插入区间（不是只有一处）
② 在【内存里】剥离 ⇒ 断言结果 == 画环前的备份
③ 断言通过后才落盘（断言失败则【拒绝写盘】）
④ 落盘后用 Get-FileHash 做【字节级】复核 · 再与备份逐字节比对
```

### 定位结果 —— 插入其实是**两段/一段**（不是一处）

| 文件 | 插入段 | 位置（字符） | 长度 |
|---|---|---|---|
| `js/cityapp.js` | **段 A**：调用点 `Rings();\n    draw` | [17220, 17237) | 17 |
| | **段 B**：注释 + 常量 + `function drawRings()` | [20548, 22545) | 1997 |
| `js/client.min.js` | **单段**：`/*5环*/{ … c.restore() }` | [54882, 55545) | 663 |

⇒ ⭐ **两处都是【纯插入】**（备份侧对应区间长度为 0）
⇒ ⭐ 所以上一班"纯新增自证 = ✅"的说法 **成立** —— 但**并非单点插入**（`cityapp.js` 是两段）

---

## 三、⭐ 判据（全部实测 · 非推断）

### ① sha256 回到改前值

| 文件 | 改前（期望） | 撤后（实测） | |
|---|---|---|---|
| `js/client.min.js` | `A6A0AEB84AE81432` | **`A6A0AEB84AE81432`** | ✅ |
| `js/cityapp.js` | `4D2A46A344DA31D9` | **`4D2A46A344DA31D9`** | ✅ |
| `sh.html` | （不回到原值 · 见第四节） | **`AD3C015F21BCAA20`** | ✅ |

### ② 与「画环前备份」逐字节比对（**第三方判据**）

```
js/client.min.js   ✅ 与剥前备份【逐字节相同】
js/cityapp.js      ✅ 与剥前备份【逐字节相同】
```

### ③ 绘制算子计数（撤后 == 备份 · 且比有环版少 2）

| 文件 | 版本 | `arc(` | `fillText` | `stroke` |
|---|---|---|---|---|
| `client.min.js` | **撤后** | **0** | 3 | 14 |
| | 剥前备份 | 0 | 3 | 14 |
| | 有环版 | 2 | 5 | 16 |
| `cityapp.js` | **撤后** | **0** | 5 | 14 |
| | 剥前备份 | 0 | 5 | 14 |
| | 有环版 | 2 | 7 | 16 |

⇒ ⭐ **撤后与备份完全一致** ✓

### ④ **真跑一遍**（不只 `node --check`）

```
node --check 双 rc=0
把 drawTraffic 抽出来用 recording ctx 实际执行：
  ✅ 执行无异常
  ✅ arc = 0 · setLineDash = 0 · save/restore = 0/0
  ✅ 调用序列恢复为 drawTraffic(); drawLabels();
```

⇒ ⭐ **`save/restore` 那个顾虑随之消失** —— 因为整块 `setLineDash` 都没了 ✓

### ⑤ 残留标志检查

```
client.min.js   5环=0  drawRings=0  255,210,63=0  setLineDash=0  121.46524=0  环 0=0   ⇒ ✅ 零残留
cityapp.js      同上                                                                     ⇒ ✅ 零残留
```

### ⑥ 编码

```
两个文件 BOM=False · CRLF=0（纯 LF · 与剥前备份一致）
```

---

## 四、⚠ 缓存串**必须再 bump 一次**（今天第三次）

```
sh.html 里 js/client.min.js?v=  的演变：
   画环前        202610081100
   有环版        202610081430
   ★ 撤环后      202610081435   ← 本次
⇒ ⚠ 不动它 ⇒ 浏览器继续用【有环】的缓存 JS ⇒ 撤了也看不见
⇒ ✅ 已 bump · 并断言「除这一处外 sh.html 与剥前备份逐字符相同」
⇒ ⚠ 保留 `board.min.js?v=202610081100` 与 `css/city.css?v=202610081340`（那两个文件未改）✓
```

---

## 五、部署

```
⭐ 关键发现：js/cityapp.js 【不在服务器上】（线上 js/ 只有 board.min.js · client.min.js · cloudclient.js）
   ⇒ 所以线上只需部署 2 个文件：client.min.js 与 sh.html ✓

备份（撤环前）：
   服务器 <服务器目录>/_bak_ringsoff_20261008_143610/（含 SHA_BEFORE.txt）
   本地   <本地数据目录>\_bak_rings_withdraw_20261008\
   （另有画环前的原件：<本地数据目录>\_bak_rings_20261008\）

传输：scp 用【相对路径】（cd 到源目录再 scp 文件名）
      ⇒ ⭐ 这修掉了此前 "ambiguous target" 的问题 —— 比 base64 中转简单
      ⇒ /tmp 侧 sha256 与本地【逐字节相同】后才 install ✓

安装后线上 sha256：
   a6a0aeb84ae81432…  js/client.min.js   ✅
   ad3c015f21bcaa20…  sh.html            ✅
⭐ 静态文件 ⇒ 【未重启任何服务】✓
```

### 线上验证（独立 HTTP 拉取）

```
12 个 URL：sh.html / index.html / versions.html / board.html / /city/ /
          client.min.js / client.min.js?v=…1435 / board.min.js /
          city.css?v=…1340 / api/state / data/shanghai.json  ⇒ 【全部 200】✓
          /city/city.html                                    ⇒ 【404】（此前已删）✓

HTTP 拉回的 client.min.js sha16 = A6A0AEB84AE81432  ⇒ 与本地/备份一致 ✓
HTTP 拉回的 sh.html        sha16 = AD3C015F21BCAA20  ⇒ 与本地一致 ✓

sh.html 内容：新串 ?v=…1435 ✅ 在 · 旧串 ?v=…1430 ✅ 已无 ·
             id="govbar" ✅ 在 · 「魔都」✅ 在 · 「上海」= 0 处 ✓

client.min.js 内容：5环 / drawRings / 255,210,63 / setLineDash / 121.46524 / 环 0 ⇒ 【全 0】✓
                   arc( = 0 ✓
```

### 其它图层/机制 **均未受影响**（逐项实测）

```
拥堵配色（红 / 黄橙 / 绿）   各 1 处 ✅
区域填色（蓝）· 区界描边      各 1 处 ✅
黄浦江/苏州河描边            2 处 ✅
夜间遮罩                     1 处 ✅
市民点提示图例               2 处 ✅
城市名「魔都」               2 处 ✅
```

### 政府层 **仍在算** · 服务 **未受影响**

```
/api/state:  day=1985 · pop=547,956
             policy{subway:3, hospital:3, school:3, green:3}
             gov.exposed=true · enabled=true · treasury=0.732 亿 · ratio=11.14
             gov.levels={"subway":3,"hospital":3,"school":3,"green":3}
systemd:     ActiveState=active · NRestarts=0 · ActiveEnterTimestamp=2026-10-08 13:51:01（未变）✓
```

---

## 六、⚠ 两条**要说明的**（避免误判）

```
⚠ ① 【green 从 2 级变成 3 级】不是本次操作造成的 ——
     那是政府层【自己把绿地升了一级】（treasury 从 6.43 亿降到 0.73 亿 = 花掉的钱）。
     ⇒ ⭐ 本次操作【只动站点静态文件】· 未碰引擎 · 未重启服务 ✓

⚠ ② 【撤环前后 · 环线颜色都是黄】不是回归 ——
     因为 congestion ≈ 0.4659 > 0.45 阈值 ⇒ 本来就该是黄的 ✓
```

---

## 七、回滚材料（三处 · 请勿删）

| 用途 | 路径 |
|---|---|
| **画环前原件**（本次恢复的目标） | `<本地数据目录>\_bak_rings_20261008\` |
| **有环版**（若要恢复环） | `<本地数据目录>\_bak_rings_withdraw_20261008\` |
| **线上撤环前** | 服务器 `<服务器目录>/_bak_ringsoff_20261008_143610/` |

**若要恢复环**：把 `_bak_rings_withdraw_20261008\` 里的 `js_client.min.js` / `js_cityapp.js` / `sh.html`
按原名放回 `deploy_city\js\` 与 `deploy_city\`，再部署 ⇒ 即回到有环版（含 `?v=…1430`）。

---

## 八、未做

```
❌ 未做浏览器目视确认（本地无浏览器）⇒ ⭐ 请用户 Ctrl+F5 强刷后确认圆环已消失
❌ 未改 MAP_RINGS_REPORT.md —— 它如实记录了"当时做了什么" · 本文件记录"后来撤了"
✅ 未碰 GitHub（本次只动线上站点静态文件）
```
