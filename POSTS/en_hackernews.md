# Show HN: A virtual city of 540k people that has been running for 1000+ days

**⚠️ 发帖前必读（不是正文 · 发的时候删掉）**
```
· 平台：Hacker News · 用 "Show HN:" 前缀
· 时机：美东 08:00–10:00（周二~周四最好）
· ⚠️ 发完【不要】自己顶帖刷评论；有人问就答
· ✅ Repo URL filled · ✅ contact filled (`wudonghai@126.com`, see `README.md`)
· ⚠️ 若被质疑"这不就是 ABM 吗" ⇒ 用 FAQ_REBUTTALS.md 第 1 条
```

---

## 正文（**从这里开始复制**）

Hi HN,

For the last few months I've been running a **behavioral virtual city** on a single server.
It's currently at **day 1456**, with **539,742 simulated people** — each one has a name, a
family, a Big-Five personality profile, an education history, a job, an income, a
relationship history, a fertility history, and a **year-by-year life trajectory**.

It is **not** an age-structured cohort model. Every individual is simulated separately,
and the population-level numbers **emerge** from individual behavior.

> **⭐ You can watch it right now:** **<https://www.faceabc.com/city/index.html>**
> — it runs 24/7 and everyone sees the same city.
> ⚠️ **One caveat: the demo UI is in Chinese** (this post is the English write-up).

> **⚠️ Three different numbers — please don't conflate them:**
>
> 1. **The live city** (above): **539k individuals**, currently **day 1456**, running in real time.
> 2. **The two runs behind the charts I'll link**: **2,500 individuals → 23,006 after 200 years.**
>    A 200-year horizon forces a small starting population — the time budget is linear in
>    (people × years), so a 539k × 200-year run would take ~9 days per arm on this machine.
> 3. **Largest I've actually run**: **210k individuals**; the largest *with full year-by-year
>    history* is **166k × 173 years**.
>
> So a chart showing ~23k is not an understatement — it's that **"long horizon" and "large
> scale" can't both be had yet.** That trade-off is written up in the limitations doc.

**The thing I actually built it for is counterfactuals.** Same people, same random seed,
**change one policy switch** → two trajectories diverge. That's the experiment you can't
run on real people.

**Three things I found that I did not expect:**

**1. I made my own model wrong in a way that reverses conclusions.**
Education is drawn from a district's **all-ages** education table, whose lowest bracket is
~61.6% — and that bracket is mostly *old* people. So in my model, **younger cohorts come out
less educated than older ones**. The real world is the opposite. Consequence: any
"education → marriage/fertility/income" result **may have the sign backwards** — the
education effect and the age effect are not separable. It's not a code bug; it's an
undeclared simplification, which is worse.

**2. The only statistically significant effect in a 10-seed experiment was a tautology.**
19 indicators, K=10 seeds, paired design. Exactly one came out significant: `mood`,
+33.1%, CI [13.05, 53.16], t=3.73, 10/0. Then I looked at the formula — and `mood` contains
a term that **reads the policy level directly** (`+ (policy.green - 1) * 4`). So of course
it moved: I built more parks. It's an arithmetic consequence, not a behavioral finding.
**A metric that reads the variable you changed can never be evidence about that variable.**

**3. Fixing "too poor" made the city absurdly rich.**
The city was spending Shanghai-scale money on a 2,500-person tax base — taxes covered only
~31% of maintenance. I added population thresholds so that a small city simply *doesn't
have* a subway (China's actual rule for subway approval is a 3M urban population — 国办发
〔2018〕52号). Maintenance dropped 75.9%, payment ratio went 0.22 → 1.00… and then the
**tax-to-maintenance ratio hit 98.4**. The city now accumulates money it can't spend.
I fixed "too poor" and created "absurdly rich". Both come from the same asymmetry:
**expenditure was absolute-scale, revenue is population-scaled.**

**What it can do today** (each with a reproducible artifact in the repo):
- Policy evaluation with confidence intervals across seeds — e.g. "build schools" → education
  of the 15–25 cohort: **+4.454%, CI [3.624, 5.284], t=12.139, 10/10 seeds same direction**
  (⚠️ but see limitation #1 above — this one is affected by it)
- **Fiscal loop**: taxes → budget constraint → maintenance → **depreciation** → rebuild.
  With maintenance underfunded, a subway level degraded at year 2 instead of year 6.
- **Individual life trajectories** — the thing cohort models can't give you at all
- Survey sampling from the synthetic population

**What it can NOT do — and this matters more than the above:**

- ⚠️ **It cannot predict.** There is **no systematic validation against reality.** I have a
  plan to anchor 1–2 indicators against public statistics; it is not done.
- ⚠️ **Birth *counts* are decided by an ID hash** in my model (only *timing* is modeled).
  So **any claim of the form "policy X raised the fertility rate" is invalid** — including
  any I might have made earlier.
- ⚠️ **Migration is expressed as a single net rate**, so **total emigration is identically
  zero** — the outflow branch is dead code. The model **cannot express population decline.**
- ⚠️ No economic system (income is an administrative formula, no firms/wages), no
  demographic equilibrium, no technical growth, and a strong scale effect (TFR 1.456 at
  2,500 people vs 0.875 at 40,000) — so **small runs are directional only, not extrapolable**.

All seven limitations are written up with line-level root causes:
`LIMITATIONS.md` (L-1 … L-7).

**Everything is synthetic. No real individual is in it. It is not a measurement, and it is
not a forecasting tool.**

Repo + a 877 KB sample package (a 20-life trajectory sample, a panel, a query cookbook
with per-answer confidence): **https://github.com/sevnwu/virtual-city**

Live demo (it is running right now): https://www.faceabc.com/city/index.html
⚠️ **The demo UI is in Chinese** — this post is the English write-up. There is also an
English one-pager in the repo: `README.md`.
**License**: docs & sample data are **CC BY 4.0** — cite, reuse, research, teaching and
commercial prototyping, attribution only. The **engine source and full data are proprietary**;
commercial licensing is available separately.

Happy to answer anything — especially "isn't this just ABM?" (short answer: the individual
layer isn't new; what I've been trying to get right is the **fiscal-institutional layer**
and **counterfactual hygiene** — and I'd like to be told where that's still naive.)

## 正文结束

---

## 备选标题（**HN 只看标题** · 挑一个）
```
① Show HN: A virtual city of 540k people that has been running for 1000+ days
② Show HN: I built a virtual city, then found my own model was backwards
③ Show HN: Counterfactual policy simulation with 540k synthetic individuals
```
⭐ **建议 ①**（"能看懂的画面"）· ⚠ **不要用 ②**（**自我揭短放正文里，别放标题** ——
   标题走"画面"，正文走"揭短"）✓

## ⚠️ 发帖后常见质疑的应对 ⇒ `FAQ_REBUTTALS.md`
