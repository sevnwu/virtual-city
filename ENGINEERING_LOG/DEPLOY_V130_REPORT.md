# v1.3.0 线上部署报告（2026-10-08）

**执行者**：部署 subagent · **状态**：✅ 完成并验证 · **未回滚**

---

## 一、结论摘要

```
✅ **STEP 1（代码）**：`citysim.js` / `gov_costs.json` / `versions.html` 已上线 · 逐项验证通过
✅ **STEP 2a/2b（开关）**：11 个开关全部生效（含 `CITY_POP_SCALE=43.9`）
✅ **STEP 3（验证）**：六个关键量全部保持基准 · `NRestarts=0` · 无新错误
🔴 **而过程中发现并解决了一个硬阻塞**：见第二节（**这是本次最重要的产出**）
⚠ **一个诚实的空白**：见第七节（**政府层在生产上"不可观测"**）
```

---

## 二、🔴🔴 最重要的发现：11 个开关里只有 4 个能从 env 设

```
实测（本地 + 已部署件【双向核实】）：
   grep "process\.env\.\(CITY_GOV_*\|CITY_POP_SCALE\)" js/citysim.js
   ⇒ ⭐ **只有 4 处**：`CITY_GOV_LEDGER`(:5362) · `CITY_GOV_DECIDE`(:5423) ·
      `CITY_GOV_DEPRECIATE`(:5600) · `CITY_GOV_ENABLE`(:8765)
   ⇒ ⚠ **无泛化 env 扫描循环**（grep `startsWith('CITY_GOV` ⇒ 0 命中）
   ⇒ ⚠⚠ **`city_server.js` 里 `CITY_GOV`/`CITY_POP_SCALE` 命中 = 【0】** ⇒ **没有 env→CIV 桥**

⇒ 若照原样在 systemd 里设 11 个 Environment= ⇒ **只有 4 个生效**：
   ✅ ENABLE / LEDGER / DECIDE / DEPRECIATE
   ❌ REALCOST / UNIT_SCALE / MAINT_FLEX / COST_BASE / SIZE_GATE / GREEN_GATE / **POP_SCALE**
```

**⚠ 为什么这很危险（不只是"少开几个开关"）**：

```
🔴 **会产生【半配置的政府层】**（4/11）—— 这个组合从未被验证过
🔴 **具体**：`DEPRECIATE=1` 生效 · 而 `MAINT_FLEX`/`COST_BASE`/`UNIT_SCALE` 不生效
   ⇒ **折旧会按【旧的固定资产值】跑**（green 32.5 亿/级，而不是缩放后的 5.69 亿/级）
   ⇒ ⚠ 而 `SIZE_GATE` 也不生效 ⇒ 无领域门禁
```

**⭐ 这正是本项目"第 14 类静默失效"的【镜像】**：不是"开关名写错"，
而是 **"开关根本没有读 env 的路径"** —— 症状相同（设了没反应），成因不同。

**⇒ 处置**：停下来报告 · 经父代理批准后，改走 **(B) 给 `city_server.js` 加 env→CIV 桥**。

---

## 三、桥的实现（纯新增 · 默认关 · 有留痕）

**位置**：`city_server.js` · 紧接 `const CIV = globalThis.CIV;` 之后
（远早于建城与首次 `cityStep`）· **+32 行 / +2,056 B**

**覆盖全部 11 项**：10 个布尔 + `CITY_POP_SCALE`（数值型，**且只在 >0 时写入** ——
因为 `popScale()` 把 0 视为非法并回退 1）。

**三条硬证明**：

```
① ⭐ **纯新增**：把插入块删掉后算 SHA ⇒ **精确回到 `3E4C5374E1E08AED`**（原值）
   ⇒ **既有行一字未动** ✓
② ⭐ **默认关（实证 · 非推断）**：把块【程序化抽取】成独立函数跑 5 种 env 情形
   · 无 env ⇒ `CIV keys = []` ⇒ **不写任何 `CIV.*`** ✓
   · 全 env ⇒ 11 项全设 · `POP_SCALE === 43.9`（数值）✓
   · `POP_SCALE=0` ⇒ **拒绝写入** ✓
   · 只设 1 项 ⇒ 只写 1 项 ✓
   · 显式 `0` ⇒ 写 `false`（仍是关）✓
   ⇒ **ALL PASS**
③ ⭐ **留痕两个分支都打印**（"日志里没出现"不能作判据）：
   `[bridge] env→CIV 已设 N 项：…` + `[bridge] 未设 M 项（保持引擎默认 ⇒ 等价于关闭）：…`
```

**指纹**：`city_server.js` `3E4C5374E1E08AED` → **`4DC49B4C9C449FF8`**

---

## 四、执行过程（三步 · 每步验证后再下一步）

| 步骤 | 动作 | 结果 |
|---|---|---|
| **STEP 0** | 采集基准 + 备份（8 文件） | ✅ `<服务器备份目录>/_bak_step1_v130_20261008_111124` |
| **STEP 1** | 推代码（`citysim` `81BAA4CF` / `gov_costs` `F73DA0DE` / `versions.html` `900311BA`）| ✅ 见下 |
| **STEP 2a** | 装桥 + 10 开关（**不含 GREEN_GATE**）+ 重启 | ✅ 见下 |
| **STEP 2b** | 追加 `GREEN_GATE=1` + 重启 | ✅ 见下 |
| **STEP 3** | 观察 ≈25 分钟（跨过 `day%30==0` 边界） | ✅ 见下 |

**⚠ 开关顺序**：按要求 **`POP_SCALE` 与 `SIZE_GATE` 同批 · `GREEN_GATE` 最后** ——
2a 先验证 `POP_SCALE` 生效（`subway` 保持 3），2b 才加 `GREEN_GATE`。

**⚠ 一处有意省略**：`CITY_GOV_INFRA_LIFE` **未设** ——
它既不被 `process.env` 读、也不在桥的 11 项里 ⇒ 设了也不会生效。
引擎走默认 `LIFE=40`（预设值）。**写进 drop-in 会造成"看起来设了"的假象，故不写。**

---

## 五、验证结果（逐项 · 与基准对比）

### STEP 1（代码 · 默认全关）

| 项 | 期望 | 实测 |
|---|---|---|
| 安装前 sha | `0269a820`/`cdc90957`/`1b726a29` | ✅ 三处一致 |
| 安装后 sha | `81baa4cf`/`f73da0de`/`900311ba` | ✅ 三处一致 |
| restart rc | 0 | ✅ 0 |
| `ActiveEnterTimestamp` | 变新 | ✅ `10-08 11:11:59` |
| **`day` 连续性** | **不重置** | ✅ 1909 → 1910 → 1911 |
| `policy` | 不变 | ✅ 逐字节相同 |
| env | 无 `CITY_GOV*` | ✅ |

### STEP 2a（桥 + 10 开关）

```
★ [bridge] env→CIV 已设 10 项：ENABLE · LEDGER · DECIDE · DEPRECIATE · MAINT_FLEX ·
   COST_BASE · SIZE_GATE · REALCOST · UNIT_SCALE · CITY_POP_SCALE=43.9
★ [bridge] 未设 1 项：CITY_GOV_GREEN_GATE
⇒ ✅ env 进进程：10 项全在 · `ActiveEnterTimestamp=11:19:24` · `day=1911` 连续
⇒ ⭐ **`policy` 未变**：`subway=3 hospital=3 school=3 green=2`
   ⇒ **这证明 `POP_SCALE=43.9` 生效**（`547,113 × 43.9 = 24.0M ≥ 300 万` ⇒ 地铁未被误判掉）
```

### STEP 2b（`GREEN_GATE` 最后加）

```
★ [bridge] env→CIV 已设 11 项：… GREEN_GATE=1 · CITY_POP_SCALE=43.9
★ [bridge] 未设 0 项
⇒ ⭐⭐ **决定性证据**：`GREEN_GATE=1` 已开而 `green` **仍是 2**
   ⇒ 再次证明 `POP_SCALE` 生效（`24.0M ≥ 60 万`）；若 POP_SCALE 失效 ⇒ green 会掉到 0
```

### STEP 3（观察 ≈25 分钟 · 跨过 day 1920 月结边界）

| 项 | 基准 | 实测 | |
|---|---|---|---|
| **`subway`** | 3 | **3** | ✓ |
| **`green`** | 2 | **2** | ✓ |
| `hospital` / `school` | 3 / 3 | **3 / 3** | ✓ |
| `day` | 1909 | **1921**（持续推进） | ✓ |
| `pop` | 547,113 | **547,216** | ✓ |
| `aqi` | 0.0590 | **0.0409**（改善） | ✓ |
| `powerLoad` | 0.7428 | **0.7410** | ✓ |
| `crime` | 0.0578 | **0.05797** | ✓ |
| `NRestarts` | 0 | **0** | ✓ |
| `budget` | `null` | **`null`**（无变化） | ✓ |
| 内存 | — | `MemoryCurrent` 1.05 GB / `MemoryMax` 2 GB | ✓ |

**⚠ 一个已排除的虚惊**：观察期间 `crime` 曾出现 **0.07788**（+35%）。
逐 60 秒采样显示它是**单点瞬态**，下一分钟即回落到 **0.05788**，
并在随后 8 分钟稳定在 0.0579 —— **不是趋势** ✓

**⚠ 历史错误已排除**：日志里 grep 到的
`ReferenceError: Cannot access 'CIV' before initialization`（行 20711–20767）与
`FATAL ERROR: Reached heap limit`（行 15562–17278）**全部早于本次桥（行 22379+）**：
· 前者是**此前某次尝试**把代码插在 `const CIV` 之前造成的 TDZ 错误（`city_server.js:24`）
· 后者对应 drop-in 注释里记录的 **2026-10-01** 那次堆溢出
⇒ ⭐ **桥行之后：零条 ReferenceError / FATAL** ✓✓

---

## 六、公网验证

```
/city/versions.html  HTTP 200  24,100 B   ⇒ 含 v1.3.0 且标为「当前版本」
/city/index.html     HTTP 200  10,923 B
/city/sh.html        HTTP 200   5,552 B
/city/api/state      HTTP 200  61,251 B   ⇒ day=1921 · pop=547,216 · policy 同基准
```

---

## 七、⚠ 诚实的空白：政府层在生产上"不可观测"

```
⚠ **我【无法】从外部证明政府层真的在算**：
   · 生产 `engine.log` 里 `cityGov` / `政府层` / `基建` / `折旧` / `维护费` 命中 **均为 0**
   · 日志里也【没有】`treasury` / 税收 字样（全日志 0 命中）
   · `/api/state` 的顶层键【不含】`gov`（键为：day · vday · timeScale · … · policy · budget ·
     districts · jobs · arche · news · interventions · history · events · serverTime）
   ⇒ ⚠ **测试 harness 会写 `govgate_probe.jsonl` · 而生产服务【不写】** ✓

⭐ **我手上有的（都是间接但一致的证据）**：
   ① 11 个开关**确实进了 env**（`/proc/PID/environ` 实测）
   ② 桥**确实把它们写进了 `CIV.*`**（启动留痕逐项列出）
   ③ **没有** `[gov] ⚠ CITY_GOV_ENABLE=1 但成本表不可用` 那行
      ⇒ 按 `citysim.js:8828` 的逻辑 ⇒ **`COSTS` 加载成功**（否则必然打印）
   ④ 服务稳定 · `policy` 与全部指标保持基准 · 跨过月结边界无异常
⇒ ⇒ ⭐ **结论**：**开关链路已确证打通**；而**"政府层是否在产生财政/折旧效应"仍未被直接观测**。
   ⇒ ⚠ **建议后续**：给生产加一个轻量 gov 探针（如每分钟往文件追加一行
     `day/treasury/payRatio/级数`）—— **否则"政府层已启用"永远只能靠推断** ✓
```

---

## 八、回滚材料（**两个目录都请勿删**）

```
① `<服务器备份目录>/_bak_before_v130_20261008_105831`（6 文件 · 含 `city_state.bin` 103.6 MB）
② `<服务器备份目录>/_bak_step1_v130_20261008_111124`（8 文件）
   · `citysim.before_step1.js`(0269a820) · `gov_costs.before_step1.json`(cdc90957)
   · `city_server.before_step1.js`(3e4c5374) · `versions.before_step1.html`(1b726a29)
   · `city-engine.service.before_step1` · `limits.conf.before_step1` · `baseline_api_state.json`
③ `<服务器备份目录>/_bak_step2a_v130_20261008_111920` · `_bak_step2b_v130_20261008_112516`
   （含 `gov.conf` 前一版）
```

**回滚步骤**：
```bash
# 1) 关政府层（最快 · 一条命令见效）
sudo rm -f /etc/systemd/system/city-engine.service.d/gov.conf
sudo systemctl daemon-reload && sudo systemctl restart city-engine
# 2) 连代码一起回滚
cp <bak>/citysim.before_step1.js   <服务器引擎目录>/js/citysim.js
cp <bak>/gov_costs.before_step1.json <服务器引擎目录>/gov_costs.json
cp <bak>/city_server.before_step1.js <服务器引擎目录>/city_server.js
cp <bak>/versions.before_step1.html  <服务器站点目录>/versions.html
sudo systemctl restart city-engine
```

---

## 九、当前现场

```
js/citysim.js      = 81BAA4CFFC59566B   (626,543 B)
gov_costs.json     = F73DA0DEB360B8C9   ( 13,732 B)
city_server.js     = 4DC49B4C9C449FF8   ( 56,037 B)  ← 本次唯一新增改动（+32 行）
public/city/versions.html = 900311BA1FCE14CA
服务：active (running) · PID 388086 · 起于 2026-10-08 11:25:20 · NRestarts=0
开关：11/11 已设（drop-in `/etc/systemd/system/city-engine.service.d/gov.conf`）
线上：day=1921 · pop=547,216 · policy{subway:3,hospital:3,school:3,green:2}（与基准一致）
```

## 十、未做 / 遗留

```
❌ **未给生产加 gov 探针**（见第七节）⇒ ⚠ **这是最大的遗留**
❌ **未跑 54.7 万人的本地对照批次**（内存受限 · 且会与线上抢资源）
⚠ **`_capAsset` 仍是硬编码常量表**（`COSTS` 是 IIFE 局部 const · `autoPolicy` 够不着）
   ⇒ 已知弱点 · 本补丁未加剧它（UNIT_SCALE 路径走共享函数 `CIV.__govCapAsset`）
⚠ **标度 43.9 是固定倍数** ⇒ 将来线上扩容（如 100 万虚拟人）需改为 24.0
```
