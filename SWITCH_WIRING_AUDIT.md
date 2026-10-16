# 开关接线完整性审计（2026-10-16 建立）

> **为什么要建这个工具**：本仓有一类**反复出现**的静默失效 —— **开关设了却到不了引擎**。
> 今天我在同一件事上踩了三次，其中一次**白跑了 5 小时**：
>
> | # | 开关 | 漏在哪 | 代价 |
> |---|---|---|---|
> | 1 | `CITY_EDU_UNLOCK` | `city_server.js` 的 `_BOOLS` 白名单 | 线上设了等于没设（当天已修） |
> | 2 | `CITY_HOUSE_INHERIT` | harness 注入 | 实验里继承恒关（当天已修） |
> | 3 | **`CITY_HOUSE_DIVORCE_KEEP`** | harness 注入 | **过夜 40 臂 A/B 两批逐字节相同、31 指标全 0** |

---

## 一、三条链路（任一条断了都静默）

```
【链路 1 · 线上】  systemd env → _BOOLS 白名单 → CIV.<key>
                   （宿主是**直接 require** ⇒ 引擎的 process.env 回退也可达，但属脆弱依赖）
【链路 2 · harness】env → ctx.CIV.<key>（**必须宿主显式注入**）
                   （harness 在 **vm** 里跑引擎 ⇒ `process` 不可见 ⇒ env 回退读不到）
【链路 3 · 引擎默认】`typeof CIV.X !== 'boolean'` 时读 process.env（仅链路 1 可用）
```

## 二、审计工具：`tools/audit_switch_wiring.cjs`

```
做法：
  ① 从引擎源码枚举**所有** `process.env.CITY_*` 读取点（= 引擎期望能被设的开关）
  ② 逐项判定它在链路 1 / 链路 2 上是否可达
  ③ 列出只有一条链路的项 —— 那就是"设了会静默无效"的定时炸弹
判据：**凡列出的缺口，都要有一句"为什么可以缺"或补上**
```

## 三、审计结果（2026-10-16 首次运行）

```
引擎读 env 的键 = 29 · _BOOLS 白名单 = 26 · harness 注入 = 24
```

### 3.1 已修（本次）

| 开关 | 缺口 | 修法 | 状态 |
|---|---|---|---|
| `CITY_HOUSE_DIVORCE_KEEP` | 链路 1 | 加进 `_BOOLS` | ✅ 两链路齐 |
| `CITY_HOUSE_INHERIT` | 链路 1 | 加进 `_BOOLS`（会被对象守卫拦下并记 skipped —— 这正是期望行为） | ✅ 两链路齐 |
| `CITY_HOUSE_DEFAULT_FIX` | 链路 2 | 加 harness 注入 | ✅ 两链路齐 |

**顺带发现**：`CITY_HOUSE_DEFAULT_FIX` **开/关差 8.6pp 有房率**（y10：29.99% vs 38.58%）
⇒ **此前所有住房类实验都没反映线上真实状态**（线上该开关=1）。

### 3.2 ⚠ 仍然存在的缺口（**尚未处理，需逐项判定**）

```
缺【链路1 白名单】9 个（线上 systemd 设了会静默无效）：
  CITY_CRIME_CAUSE · CITY_EDU_SUPPLY_ENABLE · CITY_FIRM_INTRO_FIT · CITY_FIRM_THOROUGH
  CITY_GOV_REALINCOME · CITY_INFRA_BASE_FREE · CITY_PARAM_WARN · CITY_TECH_SCENARIO · CITY_TIME_SCALE
  ⚠ 其中 CITY_TIME_SCALE 与 CITY_TECH_SCENARIO **线上确实在用**（systemd 里设了）
    ⇒ 它们靠 env 回退生效（脆弱），且在 vm 宿主下会失效

缺【链路2 harness 注入】13 个（**实验里恒为默认**）：
  CITY_CRIME_CAUSE · CITY_EDU_CONT · CITY_FIN · CITY_FIRM · CITY_FIRM_INTRO_FIT
  CITY_FIRM_THOROUGH · CITY_GOV_ACCT · CITY_GOV_REALINCOME · CITY_HOUSE_DEFAULT_FIX(已修)
  CITY_JUSTICE · CITY_PARAM_WARN · CITY_TECH_GROWTH · CITY_TIME_SCALE · CITY_TECH_SCENARIO
  ⚠⚠ **这一条影响很大**：`CITY_EDU_CONT` / `CITY_FIN` / `CITY_FIRM` / `CITY_JUSTICE` /
     `CITY_TECH_GROWTH` 在 harness 里出现 **0 次** ⇒
     **所有涉及这些系统的多种子实验，其实都是在"该系统关闭"的状态下跑的**
```

## 四、纪律（写进工具头注）

```
⭐ 凡新增开关，三件事一起做：
   ① 引擎里定义（含 `typeof` 守卫 + env 回退）
   ② 加进 `city_server.js` 的 `_BOOLS`（**若线上要可配**）
   ③ 加进 `tools/_soul_test.cjs` 的宿主注入（**若要在多种子实验里对比**）
⭐ 每次新增开关后，跑一次 `node tools/audit_switch_wiring.cjs`，确认两链路齐
```

## 五、工具自身的修正记录

```
本工具第一版用 `ctx.CIV.X = ` 匹配 harness 注入 ⇒ **漏掉了带 if 判断的注入**
  （如 `if (env) { ctx.CIV.CITY_HOUSE_INHERIT = Object.assign(...) }`）
⇒ 该误报已被识别；判定时以"该键在 harness 里是否出现"为辅助交叉验证
```

---

*工具：`tools/audit_switch_wiring.cjs`*
*相关修复脚本：`_add_divorce_injection.cjs` · `_add_defaultfix_injection.cjs`*
*记录 2026-10-16 · 城市侧*
