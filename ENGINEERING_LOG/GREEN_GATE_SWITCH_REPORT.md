# green 门槛开关化 —— 报告

**日期**：2026-10-07 · **执行者**：subagent
**任务**：把 `gov_costs.json` 的 `green.minPopulation = 600000`（2026-10-07 新增）包进一个默认关的开关

---

## 〇、为什么做

```
⚠ `gov_costs.json` 的 green.minPopulation 刚从 0 改成 600000 ⇒
   ⇒ 1,000 人城市的 green【现在会缺席】⇒ ⭐ 判据 A（FFA2359404C66485）会"假失败" ✓
   ⇒ ⚠ 而那【不是"开关"层面的改动】· 是 gov_costs.json 层面的 ⇒ 会让一切引用处误判 ✓
⇒ ⭐ 所以要开关化
```

---

## 一、改动（**单点 + 注入 · 两处**）

### ① 引擎 `js\citysim.js` —— **只改 `minPopOf()` 一处**

```js
function minPopOf(kind) {
  if (!COSTS) return 0;
  /* ⭐ CITY_GOV_GREEN_GATE：green 的【新门槛】默认不生效 ……（含推导与理由注释） */
  if (kind === 'green' && !(typeof CIV !== 'undefined' && CIV.CITY_GOV_GREEN_GATE)) return 0;
  const _p = COSTS.policies[kind];
  const _v = _p && _p.minPopulation && _p.minPopulation.value;
  return (typeof _v === 'number' && _v > 0) ? _v : 0;
}
```

⭐⭐ **为什么"改这一处"就够**（已实测核对）：
`minPopulation` 的读取点全库只有 `minPopOf()` 一处（`L8843`），而四处下游
（晋升闸门 `L5490`/`L5572` · 真算维护 `L8914` · 探针 `L8986`）**全部经由 `domainAbsent() → minPopOf()`**
⇒ 所以单点改动即**全局一致**（不会出现"真算与探针不一致 ⇒ 判据骗过自己"那个坑）。

⚠ **本开关只影响 `green`**：`subway`/`hospital`/`school` 的既有门槛不受它影响
（它们是既有的、已在基线内的行为）。

### ② harness `tools\_soul_test.cjs` —— **加宿主注入 + 双向留痕**

```js
ctx.CIV.CITY_GOV_GREEN_GATE = /^(1|true)$/i.test(String(process.env.CITY_GOV_GREEN_GATE || ''));
if (…) console.error(' [绿地门槛] CITY_GOV_GREEN_GATE=1 ⇒ green 的门槛【生效】· minPopulation = …');
else     console.error(' [绿地门槛] CITY_GOV_GREEN_GATE=0 ⇒ green 视为【不设门槛】（默认 · …）');
```

⭐ **双向留痕**（开与关都打印）—— 这是"第 14 类静默失效（开关从未进入 env）"的防线：
**"日志里没出现"不能作判据**，所以两个分支都必须留痕。

---

## 二、判据结果（**全部实跑 · 非推断**）

### ✅ 判据 A（默认关 · 不带 `CITY_GOV_GREEN_GATE`）

```
1,000 人 × 3 年 · SOUL_LIFE_V2=all · rc=0
   demog.csv SHA16 = FFA2359404C66485 == 期望 ✅✅  （逐字节一致）
留痕：[绿地门槛] CITY_GOV_GREEN_GATE=0 ⇒ green 视为【不设门槛】（默认 …）  ✅ 开关被读到
```

### ✅ 判据 B（打开 ⇒ 确实生效 · 且只影响 green）

配置：**2,500 人 × 3 年** · `CITY_GOV_ENABLE=1` + `CITY_GOV_SIZE_GATE=1`（`domainAbsent` 需 `sizeGateOn()`）

| 臂 | `CITY_GOV_GREEN_GATE` | probe 末行 | `demog.csv` SHA16 |
|---|---|---|---|
| **B_OFF** | （未设） | **`green=5`** · subway=1 · school=1 · hospital=1 | `4034CA40DB5A8AB6` |
| **B_ON** | `1` | **`green=0`** | `C90C52452B5362F3` |

```
✅ 两臂不同（green 5 → 0）⇒ 开关确实生效
✅ 两臂 rc=0 · 都跑到底 · 留痕各 1 行（开/关各打印了对应那句）
⭐⭐ 而 subway/school/hospital 两臂【都是 1】⇒ 证明本开关【只影响 green】✓✓
   （这正是任务要求的 —— 既有门槛不该被它动）
```

---

## 三、改动与备份

| 文件 | 改前 SHA16 | 改后 SHA16 |
|---|---|---|
| `js\citysim.js` | `DC4940EF4EFFB92E` | **`731FAB4D3C6938D4`** |
| `tools\_soul_test.cjs` | `1FC7E88A137ABD52` | **`1228373AC81D9BE7`** |
| `gov_costs.json` | `F73DA0DEB360B8C9` | **未动** |
| `city_server.js` | `3E4C5374E1E08AED` | **未动** |

**备份**：`D:\civ_soul_data\_edit_backups\greengate_20261007_2255\`
· `js_citysim.js` = `DC4940EF4EFFB92E`（已核对）
· `tools__soul_test.cjs` = `1FC7E88A137ABD52`（已核对）

**编码**：两文件均**无 BOM** · `citysim.js` **纯 LF**（9024 LF / 0 CRLF）·
`_soul_test.cjs` **纯 CRLF**（3914/3914 · 保持其原有状态）
**语法**：`node --check` 双双 **rc=0**（⚠ 但只查语法 ⇒ 已实跑验证）

**回滚**：把上面两份备份拷回原路径 ⇒ 应回到 `DC4940EF4EFFB92E` / `1FC7E88A137ABD52`
⇒ ⚠ **回滚后必须复跑判据 A** 确认为 `FFA2359404C66485`

---

## 四、怎么用

```
· 默认（不带开关）⇒ green 【不设门槛】⇒ 与旧行为逐字节一致（判据 A 不变）
· 要启用新门槛 ⇒ 设 CITY_GOV_GREEN_GATE=1（并需 CITY_GOV_SIZE_GATE=1 才会生效）
  ⇒ 2,500 人城市 ⇒ green 缺席（冻结在 ABSENT_LEVEL.green = 0）
  ⇒ 60 万人以上 ⇒ green 可正常建设
```

---

## 五、未做 / 遗留

```
❌ 未跑判据 C（2,500 × 30 年 · 看 payRatio 会不会 < 1）—— 超出本任务范围
   ⇒ ⚠ 而那才是"财政约束是否真的恢复"的核心判据
❌ 未部署线上（符合红线 —— 该改动会让线上那个 1463 天的城市行为剧变）
⚠ 已知弱点（非本任务引入）：_capAsset 仍是【硬编码常量表】而非从 COSTS 读 ——
   因为 COSTS 定义在 L8760（模块顶层）· 而 autoPolicy 在 L5328 ⇒ 作用域够不着
   ⇒ 缓解：harness 留痕会打印两边供比对
```

**红线**：线上未部署（演示页 HTTP 200）· 未 kill 进程 · 未跑构建 · 未删结果目录
