# A Behavioral Virtual City

**English one-pager.** · 中文主页 ⇒ [`README.md`](README.md) · **Version `v1.2.0` (live) · 2026-10-07**

| | |
|---|---|
| **Live demo** | **<https://www.faceabc.com/city/index.html>** — ⚠️ **the demo UI is in Chinese** |
| **Version history** | <https://www.faceabc.com/city/versions.html> |
| **Repo** | <https://github.com/sevnwu/virtual-city> |
| **License** | Docs & sample data: **CC BY 4.0** — cite · reuse · research · teaching · commercial prototyping, attribution only. Engine code & full data: **All rights reserved**. Commercial licensing: **wudonghai@126.com** |

---

## ⚠️ Read these three sentences first — before any number

> 1. **This is synthetic data.** There is **no real individual** in it. Not one record comes from a real person.
> 2. **This is model output.** It is **not a measurement** of any real city, and **not a statistical sample**.
> 3. **It must not be used for prediction.** **We have done no systematic validation** against reality
>    (see *What it cannot do* below).
>
> If you quote a number from this project **without** these three sentences, **what you are quoting does not hold.**

---

## What it is

**A virtual city running continuously on a server for 4 years (1456 days).** It contains **500,000+ simulated
individuals** — each with a **name, sex, birth, family, Big-Five personality (O/C/E/A/N), education,
occupation, income, relationship history, fertility history**, and a **year-by-year life trajectory**.

It is **not** an age-structured cohort transition matrix. It is **behavior-level**: **every individual is
simulated separately**, policy changes the conditions each individual faces, and **population-level
outcomes emerge from individual behavior**.

> ⭐ **What makes it different: it runs counterfactuals.**
> Same people · same random seed · **change one policy switch** ⇒ **two trajectories diverge.**
> **That is the experiment you cannot run on real people.**

### ⚠️ Three different scale numbers — do not conflate them

| | Scale | Horizon | What it is for |
|---|---|---|---|
| **① The live city** | **500k+ individuals** | **running in real time** | showing "it is alive" |
| **② The two runs behind the charts** | **2,500 → 23,006 individuals** | **200 years** | showing policy contrast over a long horizon |
| **③ Largest actually run** | **210,000** (short); **166,410 × 173 years** (with full history) | | scale ceiling |

> ⭐ **Why does the chart show ~23k and not 500k?**
> Because **time cost ≈ people × years** (measured **13–14 ms / person-year**):
> a **500k × 200-year run ≈ 9 days per arm** (20 arms ≈ 183 days).
> ⇒ **"Long horizon" and "large scale" cannot both be had yet** — this is **an explicit trade-off, not an omission.**

---

## What it can do (**each with a reproducible artifact in the repo**)

| Capability | In one line | Artifact |
|---|---|---|
| **Policy evaluation** | Given a policy and an indicator, run K seeds ⇒ an effect **with a 95% CI** | `GOV_MULTISEED_A2_RESULT.md` |
| ⭐ **Counterfactual comparison** | **Same seed · one switch changed** ⇒ trajectories diverge | `GOV_B_RESULT.md` |
| **Fiscal sustainability** | taxes → budget constraint → maintenance → **depreciation** → rebuild, **a closed loop** | `GOV_DEPRECIATION_REPORT.md` |
| **Individual life trajectories** | year-by-year marriage/fertility/job/health/migration event stream | `life500/lives500.jsonl.gz` |
| **Synthetic survey sampling** | sample the synthetic population to answer questionnaires | `/api/surveySample` |

**One completed causal chain** (the most solid result in the repo):

```
"Build schools" → education of the 15–25 cohort
   · 10 seeds · paired design
   · mean effect +4.454% · 95% CI [3.624, 5.284] · t = 12.139 · 10/10 seeds same direction
   · evidence: EDU_MULTISEED2_RESULT.md
   ⚠️ This result is affected by L-1 (see below).
```

---

## ⚠️ What it **cannot** do (**please read this section**)

> **Principle: an *undeclared* simplification is far more dangerous than a *known* error.**
> Every item below is **located to a line number in the code**, and **every one of them limits what you can conclude.**

### 🔴 Three that can **flip the sign** of a conclusion

| # | Limitation | Consequence |
|---|---|---|
| **L-1** | **The education–age structure is inverted vs. reality** — in the model, *younger ⇒ less educated*. Education is drawn from a district's **all-ages** table (lowest bracket ≈61.6%, mostly elderly). | ⚠ **"Education effect" and "age effect" are not separable** ⇒ any "education → marriage / fertility / income" result **may have the sign backwards** |
| **L-5** | **Six caliber ambiguities** — e.g. the *total* fertility indicator `expBirthsY` has an **endogenous denominator** ⇒ **a more effective policy makes it fall** | ⚠ **Change the caliber and the sign can reverse** |
| **L-7** | **Migration is expressed as a single net rate** ⇒ **gross emigration is identically zero** (the outflow branch is dead code) | ⚠ **Cannot express population outflow / urban shrinkage** — "bad economy ⇒ people leave" **does not exist** |

### 🔴 Two that make conclusions **invalid outright**

```
⚠️ L-6 · Birth COUNTS are decided by an ID hash (only TIMING is modeled)
   ⇒ ⭐ Any claim of the form "policy X raised the fertility rate" is INVALID.
   ⇒ This is the easiest one to misuse: you could say "the subsidy raised fertility" — and be wrong.

⚠️ L-6 · No validation against reality
   ⇒ ⭐ "Does it look like the real city?" CANNOT currently be answered.
     We only have a plan to anchor 1–2 indicators. Evidence: REALITY_ANCHOR_PLAN.md (plan; not implemented).
```

### 🟠 Others (**each one constrains which questions you may ask**)

```
· L-2  District education formula is biased high overall (partially fixed; residual remains)
· L-3  "Build schools" in the model means "lower schooling pressure ⇒ encourage births",
       NOT "raise human capital" ⇒ do not read it as a human-capital return
· L-4  The fiscal constraint applies ONLY to manual interventions; the "municipal bankruptcy"
       state is structurally impossible (it is only a purchasing-power gate)
· L-6  No economic system (income is an administrative formula; no firms/banks/wage market)
· L-6  No demographic equilibrium (the dead are replaced by ID-hash new souls)
· L-6  Reincarnation delay = 10 years (a test value; design value is 20)
· L-6  No technical growth (500 years is a flat line)
· L-6  housingControl is monotonically increasing; infrastructure depreciation was added late
· L-6  Attributability only reaches L3 ⇒ mechanism attribution is limited to DIRECT chains
· L-6  Strong scale effect (TFR 1.456 at 2,500 people vs 0.875 at 40,000)
       ⇒ ⭐ small runs are DIRECTIONAL ONLY · not extrapolable
```

**Full list** ⇒ [`LIMITATIONS.md`](LIMITATIONS.md) (L-1 … L-7, each with symptom / line-level root cause / impact / fix direction)

---

## 🔬 Reproducibility (**we give fingerprints, not "trust us"**)

> ⚠️ **"Same fingerprint + same parameters + same seed ⇒ byte-identical `demog.csv`"** —
> that is the only reproducibility promise we make.

**① Engine fingerprints** (SHA256, first 16 hex digits; **measured on the offline batches in this repo**):

| File | Fingerprint |
|---|---|
| `js/citysim.js` | **`0269A820E07E0423`** |
| `tools/_soul_test.cjs` | **`8E5C3E51E09AB5A9`** |
| `gov_costs.json` | **`CDC90957250ADC70`** |

⚠️ **Note**: these are an **unreleased development build** (8,985 lines) —
while **the live demo runs `v1.2.0` (8,364 lines)**.
⇒ **They are not the same binary**: the offline numbers in this repo come from the dev build,
the demo page from `v1.2.0`.

**② Data batches** referenced by this document and the charts:

| Batch | Scale | Years | Purpose |
|---|---|---|---|
| `govB_ctrl_sh` / `govB_treat_sh` | 2,500 → 23,006 / 22,685 | **200** | ⭐ the two chart curves |
| `gov_ms_A2` | 2,500 · **10 seeds × 2 arms** | 20 | first policy evaluation with CIs |
| `mem200k_sh` | **210,218** | short | scale ceiling + memory calibration |
| `run500_sh` | **166,410** | **173** | largest batch with full yearly history |
| live service | **500k+** | **day 1456** (real time) | ⭐ the demo page |

**③ Key parameters**: `SOUL_LIFE_V2=all` · `CITY_GOV_INFRA_LIFE=40` · snapshot origin **day 730**
· seed `(20250914 + k×2654435769) >>> 0` · time scale **600:1**

**④ Calibration models** (we measured ourselves):

```
time  ≈ 13–14 ms / person-year       (3-point fit; predicts completed batches to within ~10%)
memory ≈ 101 + 5.14 × √alive  MB     (7-point fit; ±4%; includes the ledger term)
```

⚠️ **Calibration coverage**: **population ≤ 210,000 · years ≤ 3** (memory).
⇒ **Anything outside this range is extrapolation** — and this model **was overturned twice in one day
by our own new measurements** (see `SHANGHAI_SCALE_BUDGET.md`).

**Full field dictionary** ⇒ [`DATA_SPEC.md`](DATA_SPEC.md)

---

## Sample data (**start here**)

[`SAMPLE_PACKAGE/`](SAMPLE_PACKAGE/) — 7 files · 877 KB:

| File | Contents |
|---|---|
| `README.md` | three disclaimers + table granularity + reproduction parameters |
| `sample_souls.csv` | 100 rows · 4 cols (personality / outcome) |
| `sample_panel.csv` | 200 rows · 17 cols (panel) |
| `sample_lives.jsonl` | **20 complete lives** |
| `sample_demog.csv` | 72 cols × 200 years (year anchors) |
| ⭐ `sample_queries.md` | **5 "what can this data answer"** examples, each with a **confidence** |
| `LICENSE_TODO.md` | 4 license options |

**⚠️ Delivery gotchas (we hit them so you don't have to)**:
```
① demog.csv column count VARIES by engine version (29 / 57 / 70 / 72) ⇒ match by name, not by count
② panel500 is actually .csv.gz (compressed)
③ souls500 column names CONTAIN CHINESE ⇒ you must specify UTF-8 explicitly when reading
④ a single lives500 record can reach ~30 KB (first record has 431 events) ⇒ stream it
```

**5-minute quickstart** ⇒ [`HOW_TO_USE.md`](HOW_TO_USE.md)

---

## Common challenges (**we say the harshest thing first**)

| Challenge | Our answer |
|---|---|
| **"Isn't this just ABM / microsimulation?"** | ⚠️ **This is the one we most need to answer.** See [`POSTS/FAQ_REBUTTALS.md`](POSTS/FAQ_REBUTTALS.md) #1. **If we can't answer it, it isn't novel.** |
| **"How do you know it's realistic?"** | ⭐ **Honest answer: there is no systematic validation.** Only a plan for 1–2 indicators (not implemented). |
| **"Are the parameters made up?"** | Partly. Supported vs. assumed values are **individually tagged** (official / platform / modelling assumption). |
| **"Where does the data come from? Scraped?"** | **Generated from nothing. Not from any real individual.** |
| **"Can it predict the future?"** | ⭐ **No.** That is sentence three on this page. |
| **"What is it good for?"** | Running the **controlled comparisons you cannot run in the real world**. |
| **"Why not just use an LLM?"** | An LLM can write a report — but it **cannot tell you which mechanism caused it**. |
| **"How do you know it isn't overfitted?"** | We can't. **See L-1 / L-5 / L-7** — three of them can flip the sign. |

---

## Collaboration / using this data

```
✅ Already set:
   · Repo:  https://github.com/sevnwu/virtual-city
   · Demo:  https://www.faceabc.com/city/index.html   (⚠️ UI is in Chinese)

✅ Contact (set):
   · Email: **wudonghai@126.com**
   · Collaboration / data requests: **Yes** — just send an email
```

> ⭐ **Full data, custom analysis, or commercial licensing** ⇒ **email `wudonghai@126.com`**
> with a one-line description of **what you want to do** and **at what scale**.
> **Academic and teaching uses are prioritised.**

**Current status**: ✅ **Docs and sample data are free to use now** (CC BY 4.0 — attribution only).
⚠️ **Full data and commercial licensing are not open yet** — pricing and attribution are still being decided
(see `SAMPLE_PACKAGE/LICENSE_TODO.md`).

---

## Citation

```bibtex
@misc{virtualcity2026,
  title   = {A Behavioral Virtual City: individual-level counterfactual simulation},
  author  = {sevnwu},
  year    = {2026},
  url     = {https://github.com/sevnwu/virtual-city},
  license = {CC BY 4.0},
  note    = {Synthetic data. Not a measurement. Not for prediction.}
}
```

**⚠️ Please always include the `note` line when citing** — it is the same thing as the three sentences at the top.
**⭐ Please keep the `license` line too** — it tells readers how this data may be used.
