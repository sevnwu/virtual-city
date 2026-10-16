# L-13 离婚析产修复 · 实施方案（待实施）

> 结论先行：**这一项与前几项不同 —— 它需要"产权归属"建模，会动 compact 列表与 ARR，
> 因此属会破判据 A 的改动，必须专门一次会话按完整铁律做。**
> 本文把它写到"照着做就行"的程度。

---

## 一、为什么必须先建"产权归属"

```
现状：`city.own` 是**每人一个 0/1 标记**
  ⇒ 夫妻有房时 **两人都是 1**（houseStep L9457 `city.own[i]=1; city.own[sp]=1;`）
  ⇒ 所以"把房判给一方"在现有结构下**表达不出来**
     （把另一方设 0 只表示"他没房"，而房子就此消失，与今天的行为等价）

离婚析产现在做的事（L7405-7417，对**双方**都执行）：
    city.wealth[x] += 房价净值 × 份额     ← 房产折成现金
    city.mortgage[x] = 0; city.mortgagePay[x] = 0;
    city.own[x] = 0                        ← 双方都失去产权
    city.noBuyUntil[x] = day + 365×2       ← 再锁 2 年

⇒ 一处房子在离婚时**没有任何一方继续持有** ⇒ 每离婚一次净减 1~2 个房主
⇒ 实测：这是线上有房率从标定 61.3% 衰减到 41.7% 的**主因**（−20pp 里的大头）
```

## 二、实施方案（按本仓铁律的顺序）

### 步骤 1：新增按人数组 `city.ownerOf`（产权归属）

```
语义：`city.ownerOf[i]` = 该人持有的房产对象 id；-1 = 无房
     夫妻共同持有的房 ⇒ 双方 ownerOf 指向**同一个** houseId
     （这才能表达"房子由两人共有，离婚时可判给其中一方"）

⚠ 新增按人数组必须同时做三件事（本仓纪律，漏一项就是静默失效）：
  ① `compact()` 的两个重排列表（约 L10349 附近）都要加
  ② `ARR` 快照清单（`_audit_compact_lists.cjs` 会校验：按人数组 53 / compact 66 / ARR 65）
  ③ `create()` 里初始化；快照恢复时缺字段 ⇒ 按 `own` 反推（缺则补齐，绝不抛）
```

### 步骤 2：新增独立开关 `CIV.CITY_HOUSE_DIVORCE_KEEP`

```
默认 **false** ⇒ 完全走既有行为（判据 A 必须保持 9018C2374EB44444）
打开后：离婚时房子**判给一方**，另一方拿现金补偿
  判定依据（按现成的 `_share` 逻辑，不另立规则）：
    · 都没还过本金 ⇒ 各 50%（此时约定"登记在收入较高方"，可解释）
    · 一方还过、另一方 0 ⇒ 判给**还过**的那一方（用户原意：没还过的不分房）
    · 都还过 ⇒ 判给 `_share` 较大者
  现金补偿 = 房产净值 × 另一方的份额（从取得方的财富里扣 ⇒ **不凭空造钱**）
  ⚠ 取得方要承接全部债务（`mortgage` / `mortgagePay` 一并转移）
  ⚠ 未取得方仍写 `noBuyUntil`（2 年）—— 他确实失去了住房
  ⚠ 取得方**不写** `noBuyUntil`（他仍有房，不该被禁购）
```

### 步骤 3：同步所有读 `own` 的地方

```
搜索：`city.own` 全部读取点（住宅计价 `netWorth` L616-620 · `houseStep` L9343 `own[i]||own[sp]`
      继承 `cityInheritHousing` L9006/L9030/L9038 · 离婚析产 · `houseStep` 购房置位 L9457
      存量标定 `citySeedHousing` L11203-11204 · `city_server.js` 的有房率统计）
⚠ 原则：`own` 保持"该人是否有房"的语义不变 ⇒ 只在**离婚析产**里用 `ownerOf` 分辨归属，
  其余读取点不动（把改动面压到最小）
```

### 步骤 4：验证（本仓铁律，缺一不可）

```
① 判据 A：开关关闭时保持 9018C2374EB44444
② `_audit_compact_lists.cjs`：按人数组/compact/ARR 三者一致
③ 改前/改后对拍（tools/_discriminate_own_decay.cjs 的框架）：
     有房率轨迹 · 有贷轨迹 · 人均财富 · 离婚数
     预期：有房率衰减显著变缓（从 61.3%→41.3% 变成 61.3%→约 55%+）
④ 跨种子 95% CI（tools/_multiseed.cjs + `_multiseed_report.cjs`，探针已接入）
     ⚠ 住房指标现在在 `housing_probe.jsonl` 里 ⇒ 需把 housing 探针也加进报告 METRICS 第四层
⑤ 新增开关进 `_BOOLS` 白名单（否则 systemd 设了也无效 —— 本仓第 N 次同类问题）
⑥ 线上部署：就绪轮询 + 四端点 + 权威口径验收 + 实测有房率
```

## 三、⚠ 同时发现一条可立即修的白名单缺口

```
实测 `_BOOLS`（26 项）里 **没有**：
  · `CITY_HOUSE_INHERIT`  ← 而 systemd 里设了 =1
  · `CITY_HOUSE_DIAG`

⚠ 但**这条需要谨慎**，不能照搬前几次的修法：
  `CITY_HOUSE_INHERIT` 是**对象**（`CIV.CITY_HOUSE_INHERIT = { enable: 0|1 }`），
  而 `_BOOLS` 的桥会把匹配项写成**布尔** ⇒ 直接加进去会**把参数块覆盖成布尔**
  （本仓已知的"命名空间冲突型覆盖"，L139-151 的守卫正是为此而加）。
  ✅ 正确修法：桥里对 `CITY_HOUSE_INHERIT` 做**对象感知**处理 ——
     `CIV.CITY_HOUSE_INHERIT = Object.assign({}, CIV.CITY_HOUSE_INHERIT_DEFAULT, { enable: v ? 1 : 0 })`
  ⚠ 但对象守卫要求"CIV 上已存在且为对象才跳过"；而 INHERIT 在桥执行时可能尚未定义
     ⇒ 需要在桥里**先确保对象存在**再写 enable。

⚠ 另：宿主是**直接 require**（非 vm）⇒ 引擎的 `process.env` 回退**理论上仍生效**，
  所以 `CITY_HOUSE_INHERIT=1` 在线上**可能已经生效**（与 CITY_HOUSE_SEED_BOOTSTRAP 不同 ——
  后者引擎里根本没定义 `CIV.CITY_HOUSE_SEED_BOOTSTRAP` ⇒ 100% 静默失效）。
  ⇒ **需先实测确认**（例如临时打开诊断计数看 `_houseInherit.moved` 是否在涨），再决定是否动桥。
```

## 四、优先级建议

```
① 【可立即做】确认 CITY_HOUSE_INHERIT 在线上是否真的生效（实测，不靠推断）
② 【需专门会话】离婚析产修复（本文 §二 的 4 步）
③ 【需外部数据】确定"住房自有率"口径 ⇒ 校准 houseOwnTarget（当前 59.6% 是平台假设）
④ 【顺手】把 housing_probe 加进报告第四层（这样住房类改动也能走标准多种子流程）
```

---

*记录 2026-10-16 晚 · 城市侧*
