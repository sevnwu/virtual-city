# 5 分钟上手

## 0. 先读那三句

```
① 这是【合成数据】—— 没有任何真实个体
② 这是【模型输出】—— 不是测量，也不是统计抽样
③ 它【不能用于预测】—— 没有做过系统性真实对照
```

## 0.5 ⭐ 先看它"活着"（**30 秒 · 不用装任何东西**）

> **在碰数据之前，先看一眼那座城本身。** 仿真在服务器上 7×24 跑，**打开就是当前状态**。

```
⭐ 演示页：  https://www.faceabc.com/city/index.html
⭐ 实时状态： https://www.faceabc.com/city/api/state     ← 一个 JSON · 可以直接 curl
⭐ 版本记录： https://www.faceabc.com/city/versions.html
```

**实测（2026-10-07）**：
```
day = 1456 · pop = 539,742 · timeScale = 600:1
births = 49,070 · deaths = 16,968 · marriages = 2,012
migIn / migOut = 5 / 0        ← ⚠️ 这一行就是 L-7（迁出恒为 0）的现场证据
```

⚠️ **注意三件**：
```
① 演示页是【54 万+ 人】· 而样例包是【2,500 → 2.3 万人】的 200 年批次 —— 两个不同规模
② ✅ 演示页**已允许搜索引擎收录**（2026-10-07 起：`index,follow`）· 可直接引用
③ 演示页是【只读】的（window.CITY_VIEW_ONLY = true）⇒ 你改不了它 · 只能看
```

⇒ ⭐ **API 是公开的、只读的 · 可以直接写脚本轮询它** —— 这是**最容易验证"它真在跑"**的方式。

## 1. 拿到样例包

`SAMPLE_PACKAGE/`（877 KB · 7 个文件）：

| 文件 | 粒度 | 行数 × 列数 |
|---|---|---|
| `sample_souls.csv` | **灵魂**（一个虚拟人） | 100 × 4 |
| `sample_panel.csv` | **灵魂-年**（面板） | 200 × 17 |
| `sample_lives.jsonl` | **一生**（事件流） | 20 条 |
| `sample_demog.csv` | **城市-年**（年锚点） | 200 × 72 |
| `sample_queries.md` | ⭐ **能回答什么问题** | 5 个例子 |
| `README.md` · `LICENSE_TODO.md` | 说明 | — |

## 2. 读它（**四个坑别踩**）

```python
import pandas as pd, gzip, json

# 坑 1：souls 的列名【含中文】 ⇒ 必须显式指定 UTF-8
souls = pd.read_csv("SAMPLE_PACKAGE/sample_souls.csv", encoding="utf-8")
print(souls.columns.tolist())
# ['sid', 'lives', '各世性别', '平均结局人格(O|C|E|A|N)']

panel = pd.read_csv("SAMPLE_PACKAGE/sample_panel.csv", encoding="utf-8")

demog = pd.read_csv("SAMPLE_PACKAGE/sample_demog.csv", encoding="utf-8")
print(len(demog.columns))          # 72 —— 但见坑 2

# 坑 3：lives500 单条可达 ~30 KB ⇒ 【必须流式处理】，别整个读进内存
eng = "平均结局人格(O|C|E|A|N)"    # 五维顺序固定为 O,C,E,A,N
with open("SAMPLE_PACKAGE/sample_lives.jsonl", encoding="utf-8") as f:
    for i, line in enumerate(f):
        life = json.loads(line)
        print(life["sid"], len(life.get("events", [])), len(life.get("yearly", [])))
        if i >= 2: break
```

```
⚠️ 坑 2（**最容易被误判**）：`demog.csv` 的【列数随引擎版本变化】——
   实测见过 29 / 57 / 70 / 72 四种。**72 只是最新版。**
   ⇒ ⭐ **认列名 · 别认列数** —— 否则你会以为"缺了 15 列"
⚠️ 坑 4：`panel500.csv` 在完整数据集里实际是 **`.csv.gz`**（压缩的）
```

## 3. 看"这套数据能回答什么问题"

打开 `sample_queries.md` —— **5 个例子 · 每个都标了置信度**：

```
① 建学校对学历的影响  —— ⭐ 已验证：+4.454% · CI[3.624,5.284] · t=12.139 · 10/0
                           ⚠️ 但受 L-1（学历年龄结构反号）影响
② 生育意愿 vs 实际    —— ⚠️ 含 expBirthsY 的【分母内生】陷阱（总量指标会反向）
③ 婚姻匹配池          —— 漏斗 marWant → marTry → marCand → marMp
④ 队列构成            —— ⚠️⚠️ 有 L-1 反向队列陷阱（方向可能反）
⑤ 个体一生轨迹        —— ⭐ 本系统最独特的输出（分年龄组模型给不出）
```

**⇒ ⭐ 读任何一个数字之前，先看它标了哪条 `L-x`** —— 然后去 `LIMITATIONS.md` 查那条。

## 4. 什么时候**不该**用它

```
🔴 想预测未来 ⇒ 别用（第三句免责）
🔴 想问"政策能不能提高生育【数量】" ⇒ 别用（出生数量由 ID hash 决定）
🔴 想研究【人口流出 / 城市收缩】 ⇒ 别用（总迁出恒为 0）
🔴 想研究【学历 → 行为】的方向 ⇒ 特别小心（L-1 会让方向反）
🔴 想把小规模结果外推 ⇒ 别用（2,500 人 tfr 1.456 vs 4 万人 0.875）
🟢 想【生成假设 / 排查机制断在哪一环】 ⇒ ✅ 这才是它的用途
🟢 想做【真实世界做不到的对照】 ⇒ ✅ 这是它的核心
```

## 5. 遇到问题

```
· 字段含义 ⇒ DATA_SPEC.md（逐个字段 · 含生成方法）
· "为什么对不上真实" ⇒ LIMITATIONS.md（L-1~L-7 · 含行号级根因）
· "这个数字能不能引" ⇒ LIMITATIONS.md 末尾的【使用规则】
```
