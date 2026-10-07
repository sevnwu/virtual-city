# Reddit 帖（r/artificial 或 r/simulation · 英文）

**⚠️ 发帖前必读**
```
· 子版建议：r/artificial（大）· r/simulation（精准）· r/complexsystems（精准）
· ⚠️ Reddit 讨厌营销 ⇒ **正文里【绝对不要】出现"求投资/求合作"**
· ⚠️ 结尾用**提问**收尾（Reddit 的互动靠问题，不靠结论）
· ⚠️ 若被质疑"这不是 ABM 吗" ⇒ 见 FAQ_REBUTTALS.md 第 1 条（**分层承认**）
```

---

## 正文（**从这里开始复制**）

**Title:**
```
I've been running a behavioral virtual city for 1000+ days (540k individuals). Here's what broke.
```

**Body:**

I've been building a **behavioral virtual city** — currently **day 1456**, **539,742 simulated
individuals**, each with a name, family, Big-Five profile, education, job, income, relationship
history, fertility history, and a year-by-year life trajectory.

Not an age-structured cohort model. Every individual is simulated; population numbers emerge.

> **⭐ It's running right now and you can watch it:** <https://www.faceabc.com/city/index.html>
> — 24/7, everyone sees the same city.
> ⚠️ **Caveat: the demo UI is in Chinese** (this post is the English write-up).

> **⚠️ Three scale numbers — please don't conflate them:**
> - **Live city**: **539k individuals**, **day 1456**, running in real time.
> - **The two runs behind my charts**: **2,500 → 23,006 over 200 years.** A 200-year horizon
>   forces a small start — time cost is linear in (people × years), so 539k × 200 years would
>   be ~9 days per arm on this machine.
> - **Largest actually run**: **210k**; largest *with full year-by-year history*: **166k × 173 years**.
>
> A chart showing ~23k isn't an understatement — it's that **"long horizon" and "large scale"
> can't both be had yet.** The trade-off is written up in the limitations doc.

**The reason I built it: counterfactuals.** Same people, same seed, change one policy switch →
two trajectories diverge. It's the experiment you can't run on real people.

**Three things that surprised me:**

**1. I made my own model wrong in a way that flips conclusions.**
Education is drawn from a district's *all-ages* table, whose lowest bracket (~61.6%) is mostly
elderly people. So in my model **younger cohorts come out less educated**. Reality is the
opposite. Measured: the 25–29 cohort's mean education fell **54.4%** over 10 years while 30–34
and 40–49 both rose.

Consequence: any "education → marriage / fertility / income" result **may have the sign
backwards**, because education and age effects aren't separable in my model. It's not a code
bug — it's an **undeclared simplification**, which is more dangerous than a known error.

**2. The only significant effect in a 10-seed experiment was a tautology.**
19 indicators, K=10, paired. Exactly one significant: `mood`, +33.1%, CI [13.05, 53.16],
t=3.73, 10/0. Then I read the formula: it contains `+ (policy.green - 1) * 4` — it reads the
policy level directly. I built more parks, so of course it moved.

**A metric that reads the variable you changed can never be evidence about that variable.**

**3. I fixed "too poor" and got "absurdly rich".**
The city spent Shanghai-scale money on a 2,500-person tax base — taxes covered ~31% of
maintenance, so it could never afford capital projects and infrastructure didn't change for 18
years. I added **population thresholds** (a small city simply doesn't have a subway — China's
actual rule requires 3M urban population, 国办发〔2018〕52号). Maintenance dropped **75.9%**,
payment ratio went **0.22 → 1.00**… and then the tax-to-maintenance ratio hit **98.4**.

Both failures are the same failure: **expenditure was absolute-scale, revenue is population-scaled.**

**What it CANNOT do — more important than the above:**
- ⚠️ **It cannot predict.** No systematic validation against reality; I only have a plan to
  anchor 1–2 indicators.
- ⚠️ **Birth *counts* are decided by an ID hash** — only *timing* is modeled. So any
  "policy X raised fertility" claim is invalid.
- ⚠️ **Migration is a single net rate → total emigration is identically zero** (outflow branch
  is dead code). The model **cannot express population decline**.
- ⚠️ No economic system, no demographic equilibrium, no technical growth, and a strong scale
  effect (TFR 1.456 at 2,500 people vs 0.875 at 40,000) → small runs are directional only.

All seven limitations (L-1 … L-7) are written up with line-level root causes.

**Everything is synthetic. No real individual is in it. Not a measurement, not a forecasting tool.**

Repo + 877 KB sample package (incl. 20 full life trajectories and a query cookbook with
per-answer confidence): **https://github.com/sevnwu/virtual-city**

Live demo (running right now): https://www.faceabc.com/city/index.html
⚠️ **The demo UI is in Chinese** — this post is the English write-up; an English one-pager
(`README_EN.md`) is in the repo.
**License**: docs & sample data are **CC BY 4.0** — cite, reuse, research, teaching and
commercial prototyping, attribution only. The **engine source and full data are proprietary**;
commercial licensing is available separately.

**Question for this sub:** my honest guess is the *individual* layer isn't novel (it's ABM), and
what I've been trying to get right is the **fiscal-institutional layer** and **counterfactual
hygiene**. If you know prior work that already did that, I'd genuinely like the pointers — it
would save me a lot of time.

---

## ⚠️ 附加（发在 Reddit 的注意事项）
```
· ⚠️ **karma 低的新号发长帖容易被自动删** ⇒ 先养号 · 或请有 karma 的人代发
· ⚠️ **不要在正文放 GitHub 链接之外的东西**（Reddit 会判 spam）
  ⇒ ⚠️ **本文档现在【多了一个外部链接】**（演示页 `faceabc.com`）
    ⇒ ⭐ **若被自动删 ⇒ 把演示链接挪到【第一条评论】里**（正文只留 GitHub）✓
    ⇒ ⭐ **或改成**："Live demo link in the first comment."
· ⭐ **评论区最该抢答的两条**：
   ① "isn't this just ABM?" ⇒ **分层承认**（个体层不新）
   ② "how do you validate?" ⇒ **一口咬定"没有系统验证"**
   ③ ⭐ **新增**："the demo is in Chinese?" ⇒ **承认 + 指向 `README_EN.md`**
      ⇒ ⚠️ **别辩解**（"我们正在做英文版"是可以的 · 但别否认现状）✓
```
