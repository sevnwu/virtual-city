# 给问卷侧的回复（第二版 · 2026-10-17）

> 可直接转发。全部结论可复跑（命令在文末）。

---

## 〇、先确认：你们撤回的两条，我都认可

| 你们撤回的 | 我的核实 |
|---|---|
| "有房率从 61.3% 衰减到 41.7%" | ✅ **该撤**。61.25% 是 `citySeedHousing` 标定后的**按人 18+**；41.7% 是引擎日志的**全体在世**（分母含约 12.7 万未成年）。**两个口径不同，不能串成"从 A 衰减到 B"**。这句出自我这边的代码注释，是我不严谨。 |
| "缺口约 16.5pp" | ✅ **该撤**。正确是**按户 8.35pp**（见下）。 |

---

## 一、但你们还有一条要撤：**"应对比成年人 54.6%，差 2.8pp"**

```
我的 /api/wealthrank 内部写的是 `if (age >= 22)`（沿引擎 hhKeyOf 的"是否仍与父母同住"门槛），
但字段名叫 `adultN`、文档叫"成年人" ⇒ **报出 54.6% 却没说门槛是 22 岁**。
⇒ 你们的 51.77% 是 18+ 口径，我的 54.6% 是 22+ 口径，**两者不可直接比**。
⇒ 这不是你们的错，是**我的字段命名与文档问题**。

同门槛下对比（18+）：
  你们 /api/surveySample 逐条计数（n=3000）：**51.27% ~ 51.77%**
  我的  ownPctPerson18                     ：**51.44%**
  ⇒ 差 0.17~0.33pp，远小于 n=3000 的标准误（**±0.91pp**）
  ⇒ **两个独立测量互相印证** ✅  这个数可信。
```

---

## 二、★ 最重要的一条：**59.6% 是"按户"目标，我们三个一直在用"按人"报缺口**

```js
// js/citysim.js L340
houseOwnTarget: 0.596,   // 【标定】按户配额：使最终有房 ≈ 60%（平台住房维 35+25）
                              ↑↑↑ 按户
```

**线上四口径实测（虚拟第 6798 天）**

| 口径 | 数值 | 分子 / 分母 | 能对 59.6% 吗 |
|---|---|---|---|
| 全体在世（含未成年） | 40.30% | 236,901 / 587,908 | ❌ 分母含 12.7 万未成年（他们不可能是房主） |
| **按人 18+** | **51.44%** | 236,863 / 460,504 | ❌ 人 ≠ 户 |
| 按人 22+ | 54.60% | 236,839 / 433,810 | ❌ 人 ≠ 户（且是我误标成"成年人"的那个） |
| **按户** | **51.25%** | **140,640 户 / 274,424 户** | ✅ **只有这个同源** |

### ⇒ 正确的缺口是 **8.35pp**

```
按户：59.6 − 51.25 = 8.35pp
```

**以下说法全部不成立，请一并作废**：
```
按全体 19.3pp（59.6−40.30）· 按人18+ 8.16pp · 按人22+ 5.00pp · 旧的 16.5pp
```

**为什么"按人"和"按户"差这么多**：夫妻同住 ⇒ 2 个有房人算 **1 户**
⇒ 236,863 个有房人只对应 **140,640 户**。

---

## 三、🔧 五条技术提醒（关于 `/api/surveySample`，你们可能没注意）

我读了抽样实现（`city_server.js` L1391-1447），有 5 点会影响你们的推断：

### ① `total` 不是"全体在世"，而是**抽样框**
```js
for (i ...) {
  if (!alive) continue;
  if (age < minAge || age > maxAge) continue;          // 默认 minAge=18
  if (hasKids) { ... }                                  // 默认不启用
  if (city.jailUntil && city.jailUntil[i] > city.day) continue;   // ⚠ 在押/服刑者被排除
  total++;
}
const stride = Math.max(1, Math.floor(total / want));
```
⇒ **抽样框 = 18 岁+ 且不在押的在世者**。在押者不在样本里（量级几百人/46 万，对有房率影响 < 0.1pp，可忽略，但要知道）。

### ② ⚠ **不要用 `?hasKids=1` 算有房率**
传了这个参数，样本只含"家里有未成年子女的成年人" ⇒ **有房家庭被系统性富集**（有孩子的家庭更可能有房），有房率会显著偏高，不能与别的口径比。

### ③ 抽样方式是**系统抽样（等间隔）**，不是简单随机
```
按数组下标顺序，每 stride 个取 1（stride = floor(total/want)）
```
⇒ 用简单随机公式估标准误（±0.91pp）**通常是保守的**（系统抽样方差 ≤ SRS，除非总体上存在与 stride 同周期的结构）。
⚠ 如果你们要做置信区间，建议至少**换两个不同 `n`** 或**换时间点重复抽样**看稳定性 —— 比信 SRS 公式更稳。

### ④ `own` 字段的判定（确认口径）
```js
const own = !!(city.own && city.own[i]);            // 房主
const withPar = (hhKey !== baseKey);                 // 与父母同住（被并入父母的户）
let housing = '租房';
if (own) housing = mort > 0 ? '自有房(有贷)' : '自有房(无贷)';
else if (withPar) housing = '与父母同住';
```
⇒ `own=1` = **自有房(无贷) + 自有房(有贷)**
⇒ **"与父母同住"的人 `own=0`**（他们住父母的房，不是房主）
⇒ 这三类互斥，加总 = 100%（你们实测 33.80+18.20+35.23+12.77 = 100 ✅ 自洽）

### ⑤ 抽样时点会变，有房率在**持续衰减**
```
我 22:xx 测 ownPctPerson18 = 51.44%（第 6798 天）
你们测的是 51.27~51.77%（可能不同天）
⇒ 引用时带上"虚拟第 N 天"，否则两次测量无法对齐
```

---

## 四、`--max-old-space-size=1800`：你们的理解要改一下

你们说"从 1400 提到 1800 了 —— 这是为了应对快照变大吗？"——**两个都不对**：

### ① 它不是为了"快照变大"，而是"只跑一个城市"的资源方案
```
来源：tools/_run_shanghai_only.sh 的注释原文
  「机器只有 3576 MB，两个引擎各约 1400 MB + 其他栈 780 MB = 3580 MB ⇒ 零余量 ⇒ swap 抖动
   ⇒ 只跑一个城市 + 关 daily + TIME_SCALE 1800 + 堆上限 1800」
⇒ 动机是**在只跑一个城市后，把省下的内存给堆**
```

### ② 它**从未生效** —— 被 drop-in 覆盖回 1400
```
主 unit   L11：ExecStart=... --max-old-space-size=**1800**
drop-in   limits.conf L35-36：ExecStart=（空，清空主 unit 的值）
                              ExecStart=... --max-old-space-size=**1400**
systemd 内存：argv[]=...1400...
运行进程：     ...1400...
⇒ systemd 规则：drop-in 的 ExecStart= 清空前者、后一行设置新值 ⇒ **drop-in 胜出 ⇒ 实际一直是 1400**
```
⚠ 你们说"今天自己也栽在同一类错上（survey_export.php 变 0 字节）"——
**是同一类但不是同一因**：你们的是"文件变空"（写入失败），我的是"文件写 A、另一处配置写 B、B 生效"。共同点是**都只能靠实测发现，看配置看不出来**。

### ③ 快照相关数字（如果你们要做备份/恢复规划）
```
快照大小      ：143 MB（data/city_state.bin）
写入耗时      ：4.0 ~ 4.4 秒（原子写：先写临时文件再 rename）
写入间隔      ：SNAPSHOT_DAYS=300 ⇒ 每 300 虚拟日 ≈ 每 72 分钟真实时间
重启恢复就绪  ：8 ~ 12 秒（期间接口返回 503，采样任务撞上会失败）
堆使用实测    ：RSS ≈ 750 MB（上限 1400，余量充足）
```

---

## 五、📎 文件在哪（你们说搜不到的原因已查明并修复）

```
原因：LANDING_PAGE 的 remote 是 github.com/sevnwu/virtual-city，
      但 git status 显示 **ahead 24**（24 个提交从未推送）⇒ 文件只在本地 D 盘
修复：已 push（04284cd..e4ac298）
```

**现在可直接打开**（GitHub API 已核实全部 200）：

| 文件 | 说明 |
|---|---|
| [ANSWER_QUESTIONNAIRE_DIVORCE_20261017.md](https://github.com/sevnwu/virtual-city/blob/main/ANSWER_QUESTIONNAIRE_DIVORCE_20261017.md) | 四个问题的完整答复（§3.2/§3.3/§六 已按本文口径修正） |
| [OWNERSHIP_SCOPE_CORRECTED.md](https://github.com/sevnwu/virtual-city/blob/main/OWNERSHIP_SCOPE_CORRECTED.md) | **口径修正（新）**：四口径机制解释 + 8.35pp 的推导 |
| [WEALTH_ASSET_SCOPE.md](https://github.com/sevnwu/virtual-city/blob/main/WEALTH_ASSET_SCOPE.md) | 资产口径：无企业股权/股市，架构层面没有这个位置 |
| [L13_DIVORCE_FIX_RESULT.md](https://github.com/sevnwu/virtual-city/blob/main/L13_DIVORCE_FIX_RESULT.md) | 离婚析产修复的跨种子 CI |
| [LIMITATIONS.md](https://github.com/sevnwu/virtual-city/blob/main/LIMITATIONS.md) | 已知偏差总表（L-13 已按按户口径更新） |

⚠ 用 `raw.githubusercontent.com` 直连会 404（CDN 缓存延迟），**用 blob 链接**或加 `?t=` 参数。

---

## 六、给客户的推荐写法（你们可直接用）

```
住房维度（有房率）：
  本模型虚拟第 6798 天实测 ——
    按户      51.25%（有房户 140,640 / 总户数 274,424）  ← 与模型目标 59.6% 同口径，缺口 8.35pp
    按人 18+  51.44%（与独立抽样 51.27~51.77% 互证，差 0.2pp）
  口径提示：不要用"全体在世"（分母含未成年，40.30% 不可比）；
           不要把"按人"与"按户"目标对比。
  性质：这是本模型"一次性标定 + 之后单调衰减"的结果，
       不是对上海真实住房自有率的估计；
       且新增成年人几乎没有获得存量住房的路径（实测 8 年新增成人 +89，成为房主仅 3 人）。
  已尝试的修复（离婚析产）经跨种子 95% 置信区间检验为「财富 −4.65% 显著、有房率 −3.18% 显著」
  ⇒ 未启用。
```

---

## 七、一条方法论，我也写进规范了

```
一个数字如果没有「分子 / 分母 / 门槛」三件套，它就不能进文档。
```

这次三方各错一处，**没有一个是算错，全是口径没说清**：
```
我   ：报了 54.6%，但没说门槛是 22 岁
你们 ：拿 18+ 的自己去对 22+ 的我
目标 ：59.6% 是按户，但历史上所有缺口都用"按人"在报
```
已按此改代码：`/api/wealthrank` 的输出**把口径写进字段名**
（`ownPctPerson18` / `ownPctPerson22` / `ownPctHousehold`），任何引用方不需要再问"这是哪个口径"。

你们要引用时，直接用字段名对应的那个数就行。

---

## 附：复跑命令

```bash
# 我的四口径（一次读出）
curl -s 'https://www.faceabc.com/city/api/wealthrank?top=1' | python3 -c "
import json,sys
j=json.load(sys.stdin)
print('按人18+  %.2f%%  (%d/%d)' % (j['ownPctPerson18'], j['ownN18'], j['adultN18']))
print('按人22+  %.2f%%  (%d/%d)' % (j['ownPctPerson22'], j['ownN22'], j['adultN22']))
print('按户     %.2f%%  (%d/%d)' % (j['ownPctHousehold'], j['hhWithOwner'], j['houseN']))
print('目标 %.1f%%  按户缺口 %.2fpp' % (j['ownTargetPct'], j['ownGapPct']))
"

# 你们的 18+ 口径（逐条计数 + 标准误）
curl -s 'http://127.0.0.1:8282/api/surveySample?n=3000' | python3 -c "
import json,sys,math
d=json.load(sys.stdin); rows=d['items']
n=len(rows); k=sum(1 for r in rows if r.get('own') in (1,True))
p=100.0*k/n; se=100*math.sqrt((p/100)*(1-p/100)/n)
print('surveySample own=1 %d/%d = %.2f%% (±%.2fpp)  day=%s' % (k,n,p,se,d.get('day')))
print('stride=%s total=%s  ⇒ 抽样框 = 18+ 且不在押' % (d.get('stride'), d.get('total')))
"

# heap 到底哪个生效
sudo systemctl show city-engine -p ExecStart --value
tr '\0' ' ' < /proc/$(pgrep -f 'city_server.js 8282'|head -1)/cmdline
sudo grep -o 'max-old-space-size=[0-9]*' /etc/systemd/system/city-engine.service
sudo grep -o 'max-old-space-size=[0-9]*' /etc/systemd/system/city-engine.service.d/limits.conf
```
