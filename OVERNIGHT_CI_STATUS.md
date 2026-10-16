# 过夜跑批状态（2026-10-16 16:25 启动）

## 一、正在跑什么

```
实验：离婚析产修复（CITY_HOUSE_DIVORCE_KEEP）的跨种子 95% 置信区间
规模：2,500 人 × 12 年 × 10 快照（K=10）× 2 臂 × 2 组 = **40 臂**
启动：2026-10-16 16:25:49（pid 28884 · detached ⇒ 不随会话结束而终止）
预计：约 11 小时 ⇒ **明早约 03:30 完成**

臂 A（基准，不带开关）→ H:\civ_soul_data\divkeep_ctrl
臂 B（开启，修复合入）→ H:\civ_soul_data\divkeep_on
日志 → civilization\tools\_offline\ab_divorcekeep\launch.log
```

**为什么跑这个**：离婚析产修复实测 **+4.17pp** 有房率，但**人均财富 −9.1 万**。
跨种子 CI 是判断"这个连锁反应量级是否可接受"的唯一依据 —— 也是决定能否开启该开关的前提。

## 二、明早怎么看（一条命令）

```powershell
node tools/_check_divorce_ci.cjs
```

它会直接打印：进度（已完成臂数）· 产物清单 · **若报告已生成则给出住房与连锁指标的
臂 A/臂 B 对比 + 跨种子 95% CI + 是否显著**。

手动看原始报告：
```
H:\civ_soul_data\divkeep_ctrl\ms<tag>_REPORT.md
H:\civ_soul_data\divkeep_on\ms<tag>_REPORT.md
```

## 三、判读标准

| 指标 | 期望 | 决策含义 |
|---|---|---|
| **ownPct**（有房率） | **显著为正** | 修复有效 |
| **avgWealth**（人均财富） | 预期为负（−9 万量级） | ★ **这是决定是否开启的关键** —— 若 CI 显示 |效应| 过大，需权衡 |
| priceToIncomeMed / rentBurdenMed | 基本不变 | 修复只动产权归属，不该改房价/租金 |
| marriages / births / divorces | 基本不变 | 若出现 >10% 或反常 ⇒ 逐案分析 |
| priceToIncomeMed 等 | — | 同上 |

**建议决策规则**：
```
若 ownPct 显著为正 且 avgWealth 的 CI 在 −15% 以内 ⇒ 建议开启
若 avgWealth 效应过大（> −20%）或出现连锁异常 ⇒ 先回来分析，别急着开
（无论哪种，结论都写进 L13_DIVORCE_FIX_RESULT.md）
```

## 四、没跑完怎么续

```powershell
# 断点续跑（已完成的臂会被跳过；带"末年必须 ≥ YEARS"校验，半成品会重跑）
node tools/_launch_divorce_ci.cjs
```

⚠ 续跑会**新起一个时间戳 tag** ⇒ 建议先看进度：若只差几个臂，等它跑完更省事；
若进程已死且剩余较多，直接重跑（编排器会按进度文件跳过已完成项，但新 tag 会重新开始
—— 若想真正续跑同一批，改用 `_run_switch_ab.cjs` 并保持同一 `--root`）。

## 五、⚠ 跑批期间不要做的事

```
· 不要在这台机器上跑另一批多种子（内存竞争 —— 今天已因这个杀掉过一次冒烟）
· 不要在 H 盘做大文件移动
· 不要重启引擎服务（跑批只读快照，不受影响；但重启会占 CPU）
```

---

*记录 2026-10-16 16:26 · 城市侧 · 过夜无人值守*
