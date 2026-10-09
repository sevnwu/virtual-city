# CITY_GOV_EXPOSE —— 让「政府层在算」从推断变成可观测

**日期**：2026-10-08 · **状态**：✅ 已部署 · ✅ 全部判据通过（含 day 1950 月结的真实账本）· **默认关 ⇒ 无 env 时行为逐字节不变**

---

## 〇、一句话

v1.3.0 已在线上启用政府层，但**生产侧无法观测**（`engine.log` 里 `cityGov`/`基建`/`折旧`/`维护费`
命中均为 0；`/api/state` 顶层键不含 `gov`；测试 harness 写 `govgate_probe.jsonl` 而常驻服务不写）
⇒ 结论**只能靠推断**。本次加两件：**① `/api/state` 的只读 `gov` 键** · **② 每分钟一行的轻量探针**，
两件同一个开关 `CITY_GOV_EXPOSE`（**默认关**）。

---

## 一、改了什么（两处插入块 · 纯新增）

| 位置 | 内容 |
|---|---|
| `city_server.js` **L576+（块 A）** | `GOV_EXPOSE` 判定 · `buildGovPayload()` · `govPayload()`（5s 节流）· 60s 探针 · **双向启动留痕** |
| `city_server.js` `statePayload()` **（块 B）** | 一行：`gov: govPayload(),` |

**指纹**：`city_server.js` `4DC49B4C9C449FF8` → **`4720930F072D6ADA`**（56,037 → 59,669 B · +3,632 B）

**关键设计**：
- ⚠ **默认关**：`GOV_EXPOSE` 为假 ⇒ `govPayload()` 返 `undefined` ⇒ `JSON.stringify` **省略该键** ⇒ 响应体不变；
  探针 interval **不注册**。
- ⚠ **只读**：只调 `CIV.cityGov.report(city)`（已核：该函数只读 `city.gov` / `city.policy`，不写任何状态）。
- ⚠ **5s 节流**：`report()` 内部会调 `taxOf(city)`（**全量扫描**）——
  而前端**每秒轮询** `/api/state` ⇒ 必须节流，否则每秒一次全量扫描。
- ⚠ **探针体积上限**：超 20 MiB 只保留最后 2000 行（诊断探针不该把磁盘写满）。

---

## 二、四条硬要求 —— 逐条如何被证明

### ① 开关 · 默认关 ⇒ 逐字节不变 —— **实测**

```
A  ：无 EXPOSE · 无 ENABLE   ⇒ 顶层键 19 个 · 【无 gov 键】 ✓
A' ：无 EXPOSE · 无 ENABLE   ⇒ 对照（同一 env 跑第二遍）
A2 ：EXPOSE=1 · 无 ENABLE    ⇒ 【有 gov 键】

diff(A, A')  = vday      ← 两次【完全相同 env】的运行，差异就是 vday（采样相位）
diff(A, A2)  = vday      ← 唯一 env 差异是 EXPOSE，差异【也是 vday】
⇒ 判定 NEUTRAL ✅（若补丁有行为影响，A2 会多出别的字段）
```
**方法学要点**：单看「A2 与 A 在 `stats`/`census`/`match` 上不同」会**误判**为补丁有害 ——
那三个量含**日内可变聚合**，两次运行采样相位（`vday`）不同就会不同。
⇒ **判定必须用「同 env 跑两遍」作噪声基线**，而不是拿一次运行当基准。

### ② 纯新增 —— **可机械证明**

```
strip（删掉两处插入块）⇒ SHA16 精确回到 4DC49B4C9C449FF8  ✅
apply 后再自检 strip ⇒ 仍是 4DC49B4C9C449FF8            ✅
```
工具：`D:\civ_soul_data\_patch_expose.cjs`（`--verify` / `--strip` / 默认 apply · 幂等）

### ③ 留痕 —— **两个分支都打印**

```
未设： [gov] CITY_GOV_EXPOSE 未设 ⇒ 不暴露 gov 键 · 不写探针（保持引擎默认 ⇒ 等价于关闭）
已设： [gov] CITY_GOV_EXPOSE=1 ⇒ /api/state 暴露 gov 键 · 探针每 60s 追加 <路径>
```
⇒ **"日志里没出现"不能作判据** —— 两个分支都有输出，才能区分「没设」与「日志丢了」。

### ④ 只读 —— 由 `report()` 的代码保证

`report()`（`citysim.js` L9059-9076）只读 `city.gov` / `city.policy`，局部累加 `maint` 后返回新对象；
不写 `city.*`。已逐行核对。

---

## 三、探针（追加逻辑 + 轮转）—— **源码级测试**

方法：**从已打补丁的 `city_server.js` 里抽取真实回调代码文本**再执行（不是重写一份），
确保测的就是将要部署的那段。工具：`_test_rotation.cjs`。

| 场景 | 结果 |
|---|---|
| 文件不存在 ⇒ 追加并创建 | ✅ 0 → 115 B · 1 行 |
| 正常小文件 ⇒ 追加 | ✅ 24 → 139 B · 4 行 |
| **22.00 MiB（超阈值）⇒ 截断至 2000 行再追加** | ✅ 23,068,794 → 403,913 B · 2000 行 · 有截断日志 |
| `buildGovPayload()` 返 `null` ⇒ 不写 | ✅ 文件未被创建 |
| `buildGovPayload()` 抛错 ⇒ 吞掉不崩 | ✅ 异常未逃逸 |

> ⚠ 第一版测试的「超 20MB」用例**实际只造出 20.39 MiB < 20.97 MiB 阈值** ⇒ 轮转分支**根本没跑到**，
> 却被算作 "✅"。**修正后**（构造 22.00 MiB）才真正验证。**教训：让"应该失败的分支"真的被触发。**

---

## 四、部署记录（两阶段 · 每阶段验证后再下一步）

| 阶段 | 动作 | 验证 |
|---|---|---|
| 1 | 上传 + 装 `city_server.js`（**env 未动**） | SHA 对 · `node --check` OK |
| 1b | `sudo -n systemctl restart` | `ActiveEnterTimestamp` 11:25:20 → **12:40:33** · **含 gov 键 = NO** ✅ · policy 不变 |
| 2 | drop-in 加 `Environment=CITY_GOV_EXPOSE=1` + `daemon-reload` + restart | 时间戳 → **12:40:42** · **含 gov 键 = YES** ✅ · policy/aqi/powerLoad/crime **完全一致** |

**部署前后关键量（应一致，实测一致）**：
```
policy = {subway:3, hospital:3, school:3, green:2, vaccine:0.4, …}
aqi = 0.11048941001177083 · powerLoad = 0.741091745095152 · crime = 0.05798732181904502
```
⇒ **`subway=3` / `green=2` 保住了**（这是 `CITY_POP_SCALE=43.9` 生效的关键判据）。

**探针**：`<服务器引擎目录>/data/gov_probe.jsonl` · 启动 60s 后开始写 · 261 B/行

**环境变量的权威复核**（三条独立证据，互相印证）：

| 证据 | 结果 |
|---|---|
| `/etc/systemd/system/city-engine.service.d/gov.conf` | **12 行 `Environment=`**（10 个 gov 布尔 + `CITY_POP_SCALE=43.9` + `CITY_GOV_EXPOSE=1`）|
| ⭐ **`/proc/<MainPID>/environ`**（最权威 · 进程真实环境） | **`CITY_GOV*` 共 11 项**，逐项列出正确 |
| ⭐ **`engine.log` L22474 / L22489**（桥与暴露的启动留痕） | `[bridge] env→CIV 已设 11 项：… CITY_POP_SCALE=43.9` · `[gov] CITY_GOV_EXPOSE=1 ⇒ …` |

> ⚠ **`systemctl show city-engine -p Environment --value` 在我的 shell 引号下读数不可靠**
> （同一命令一次给 10、一次给 0，而 drop-in 与 `/proc` 都显示 11）。
> **⇒ 结论以 `/proc/<pid>/environ` + drop-in 文件 + 启动留痕为准**，不用 `systemctl show -p Environment`。
> 这是本次第三次「工具读数不可信」——**每次都要用第二个独立通道交叉验证**。

---

## 五、⚠ 一个必须写清的期望：`treasury` 起初是 `null`

```
实测首行： {"t":…,"exposed":true,"enabled":true,"day":1941,
            "treasury":null,…,"levels":{"subway":3,"hospital":3,"school":3,"green":2}}
```

**原因（已定位，非 bug）**：
`govStep` 只在 **`city.day % 30 === 0`**（月结）触发（`citysim.js` L6706），
而 `city.gov` 由 `govStep` 内的 `init(city)` 创建。
政府层在 **11:25** 启用，当时城市在 **day 1921**；下一个 30 的倍数是 **1950**。
⇒ **day < 1950 期间 `city.gov` 为 null ⇒ `report()` 返回 null ⇒ `treasury: null`。**
`levels` 来自 `city.policy`（非 `city.gov`），所以一开始就有值 —— 这正好能区分
「**开关通了但还没生成账本**」与「**链路没通**」。

⇒ **判据：探针里出现 `treasury != null` 的行，才算"政府层确实在算"。**

### ✅ 该判据已通过（2026-10-08 12:56 · day 1950）

```
{"t":1791435828875,"exposed":true,"enabled":true,"day":1950,
 "treasury":643794076.7933075,          ← ★ 6.44 亿（真值）
 "taxCum":752404230.6033075,
 "spendCum":108610153.81,
 "months":1,
 "monthlyTax":729836015.1192346,        ← 月税 7.30 亿
 "monthlyMaint":64809824.03,            ← 月维护 6,481 万
 "ratio":11.26119421002283,             ← 税/维护 = 11.26
 "payRatio":{"subway":1,"hospital":1,"school":1,"green":1},   ← ★ 实付 100%
 "maintPaid":64809113.809999995,
 "levels":{"subway":3,"hospital":3,"school":3,"green":2}}
```

**⇒ 这是「政府层确实在算」的【首次直接观测】**（此前只能靠推断）。

**同时确认未受影响**（部署前后）：
```
policy   = {subway:3, hospital:3, school:3, green:2, …}   ← 逐字段一致
powerLoad 0.7411 → 0.7410 · crime 0.05799 → 0.05804       ← 噪声内
aqi      0.1105 → 0.0807                                  ← 随天气/季节自然变化
服务     running · NRestarts=0 · 起于 12:40:42
```

**⭐ 副产物（第一个"线上财政状态"实测）**：`payRatio` 四项**全为 1**（实付 100%）·
`treasury` 6.44 亿 ⇒ **线上财政【不紧张】**。
这与本地三次独立测量（2,500 人 / 10 万人 / `govB` 200 年）方向一致，
且与真实世界对照（上海绿地全存量 ÷ 年财政收入 = 0.17 年）相符。**不是 bug。**

### ⚠ 方法论教训（本次第二次"验证的验证"）

我用来查 `engine.log` 的脚本写了 `grep -e '[gov]'` —— 那是**字符类**（匹配 g/o/v 任一字母），
于是「`[gov]` 命中」那一节打印的其实是 `[bridge]`/`[boot]`/`[marriage]` 等行，
计数 **10408 也是无意义的**。**是"标签与内容对不上"让我看出不对**，
改用 `-F '[gov]'` 才是精确匹配（真实行只有 L22489 一条）。

⇒ **与「超 20MB 用例其实没超阈值」是同一类错误**：
**测试/检查工具本身必须先自证"能看到它该看到的东西、能报出它该报的错"。**

---

## 六、回滚

```sh
# 只关暴露（保留代码）
sudo -n sed -i '/CITY_GOV_EXPOSE=1/d' /etc/systemd/system/city-engine.service.d/gov.conf
sudo -n systemctl daemon-reload && sudo -n systemctl restart city-engine
# 连代码一起回
cp -a <服务器备份目录>/_bak_expose_20261008_123948/city_server.before_expose.js \
      <服务器引擎目录>/city_server.js
sudo -n systemctl restart city-engine
```
**备份**：`<服务器备份目录>/_bak_expose_20261008_123948/`（含 `SHA_BEFORE.txt` = `4dc49b4c9c449ff8`）

---

## 七、已知限制 / 后续

- ⚠ **`CITY_GOV_INFRA_LIFE` 未设**（drop-in 里已注明）：它既不被 `process.env` 读、也不在桥的 11 项里
  ⇒ 设了不生效 ⇒ 引擎走默认 `LIFE=40`。**不写进 drop-in 是为了避免"看起来设了"的假象。**
- ⚠ **探针是"每分钟一次"** ⇒ 相对虚拟时间约 **1.3 样本/虚拟月**（`TIME_SCALE=600`，约 46 虚拟日/小时）
  ⇒ 够看月度财政，**不够看日内过程**。
- ⚠ **`payRatio` / `maintPaid` 只有在 `CITY_GOV_MAINT_FLEX` 打开时才出现在 `lastMonth` 里**
  （`citysim.js` L9053）—— 线上该开关**已开**，所以月结后这两项应出现。
- **未做**：演示页 UI 展示 `gov` 键（本任务只要"可观测"）；探针的时序分析脚本。
- **本地源码 = 线上**：`4720930F072D6ADA`（`civilization\city_server.js`）

---

## 八、交付物

| 文件 | 说明 |
|---|---|
| `civilization\city_server.js` | **`4720930F072D6ADA`** · 已部署 |
| `D:\civ_soul_data\_patch_expose.cjs` | 补丁/移除/验证工具（幂等 · 可证"纯新增"） |
| `D:\civ_soul_data\_test_expose.cjs` | A/A2/B 三臂端到端测试（含中性对照说明） |
| `D:\civ_soul_data\_test_neutral.cjs` | **A vs A′ vs A2 噪声基线对照**（判定 NEUTRAL 的关键实验） |
| `D:\civ_soul_data\_test_rotation.cjs` | 探针追加/轮转的**源码级**测试（5 场景） |
| `D:\civ_soul_data\_smoke_probe.cjs` | 部署前冒烟 |
| `D:\civ_soul_data\_deploy_expose_s1.sh` / `_s2.sh` | 两阶段部署脚本 |
| `D:\civ_soul_data\_expose_test\` | 测试产物与日志 |
