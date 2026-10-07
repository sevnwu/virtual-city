# X / Twitter 推文串（**10 条**）

**⚠️ 发帖前必读**
```
· ⚠️ 每条 ≤ 280 字符（**已按此写好 · 别加字**）
· ⚠️ 配图最重要：**一张"虚拟城市人口曲线"或"两条轨迹分叉"的图** ⇒ 有图转发量差一个数量级
· ⚠️ 第 1 条与第 9/10 条是钩子 · 中间是内容
· ✅ 仓库 URL 已填 · ⚠️ 仅剩【联系方式】待填
· ⚠️ 编号以【正文里的】为准（**1/10 … 10/10**）—— 早先的 /8 是旧编号 · 中间几条未改
```

---

## 正文（**从这里开始复制 · 一条一条发**）

**1/10**
```
I've been running a virtual city on one server for 1000+ days.

539,742 simulated people. Each has a name, a family, a personality, a job, and a year-by-year life trajectory.

I built it to run experiments you can't run on real people.
```

**2/8**
```
The point is counterfactuals:

same people, same random seed, change ONE policy switch → two trajectories diverge.

That's the experiment nobody can do with real humans.
```

**3/8**
```
Three things I found that I didn't expect. Here's the first — and it's my own mistake.

In my model, younger cohorts come out LESS educated than older ones. Reality is the opposite.

Why: education is drawn from an all-ages table whose lowest bracket (~61.6%) is mostly elderly.
```

**4/8**
```
Measured: the 25–29 cohort's mean education fell 54.4% over 10 years, while 30–34 and 40–49 both rose.

Consequence: any "education → marriage/fertility/income" result may have the sign backwards.

It's not a bug. It's an undeclared simplification. That's worse.
```

**5/8**
```
Second: in a 10-seed, 19-indicator experiment, exactly ONE effect was significant.

mood, +33.1%, CI [13.05, 53.16], t=3.73, 10/0.

Then I read the formula. It contains a term that reads the policy level directly: `+ (policy.green - 1) * 4`.

A tautology. Not a finding.
```

**6/8**
```
Third: I fixed "too poor" and created "absurdly rich."

Taxes covered 31% of maintenance. I added population thresholds — a small city simply doesn't have a subway (China's real rule: 3M urban population).

Maintenance -75.9%. Payment ratio 0.22 → 1.00.

Then the tax/maintenance ratio hit 98.4.
```

**7/8**
```
Both failures are the same failure: expenditure was absolute-scale, revenue is population-scaled.

What it CANNOT do (this part matters more than the rest):

⚠️ It cannot predict. No systematic validation against reality.
⚠️ Birth COUNTS come from an ID hash. So any "policy X raised fertility" claim is invalid.
⚠️ Migration is one net rate → total emigration is identically zero.
```

**8/10**
```
All 7 limitations are written up with line-level root causes. Everything is synthetic — no real person is in it. It is not a measurement, and not a forecasting tool.

Repo + an 877 KB sample package (incl. 20 full life trajectories):

https://github.com/sevnwu/virtual-city
```

**9/10**
```
It is running right now, 24/7 — you can watch it:

https://www.faceabc.com/city/index.html

⚠️ One caveat: the demo UI is in Chinese. An English one-pager is in the repo (README_EN.md).
```

**10/10**
```
One scale caveat, since the chart may confuse:

The two runs behind my charts are 2,500 → 23,006 over 200 years. A 200-year horizon forces a small start — 539k × 200y would be ~9 days per arm.

"Large scale" and "long horizon" can't both be had yet.
```

---

## ⚠️ 许可说明（发帖时可放最后一条评论 · 不放正文）
```
Docs & sample data: **CC BY 4.0** (cite · reuse · research · teaching · commercial prototyping — attribution only) · Engine source & full data: **proprietary** · Commercial licensing: <TODO: contact>
```
