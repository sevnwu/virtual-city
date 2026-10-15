# 许可协议（License）

**本目录**（`SAMPLE_PACKAGE/`：样例数据文件与说明文档）采用
**Creative Commons Attribution 4.0 International（CC BY 4.0）** 许可。

> 权利人已就此拍板（2026-10-07）：**「版权这块你看着改就行 · 只要是利于传播」**
> ⇒ 本目录**现在可以对外分发**。

---

## 一、你可以做什么（CC BY 4.0 · 署名即可）

| 用途 | 是否允许 |
|---|---|
| 复制、转载、二次分发 | ✅ 允许 |
| 用于研究与论文（含商业机构的内部研究） | ✅ 允许 |
| 用于教学与课程材料 | ✅ 允许 |
| 用于商业原型验证、产品可行性测试 | ✅ 允许 |
| 修改、加工、与其他数据合并 | ✅ 允许 |
| 用于训练模型 | ✅ 允许 |

**唯一条件：署名**（见下"三、怎么署名"）。

完整法律文本见本目录 `LICENSE-CC-BY-4.0.txt`（CC BY 4.0 官方英文原文），
或 <https://creativecommons.org/licenses/by/4.0/legalcode.zh-Hans>（官方中文译本）。

---

## 二、不在本许可范围内的

| 对象 | 许可 |
|---|---|
| **仿真引擎源码**（`citysim.js` 等） | **保留所有权利（All rights reserved）** —— 未经许可不得复制、镜像、反编译或再分发 |
| **完整数据集与完整运行产物** | **保留所有权利** —— 需**另行授权** |
| **"虚拟城市"名称与相关标识** | 保留所有权利（不得用于暗示官方背书） |

> ⚠️ 本目录里的样例数据是**抽样片段**，用于让接收方判断"这套数据能用在哪"。
> 若需用于有统计意义的研究，请**索取完整批次**（另行授权）。

---

## 三、怎么署名

### 最简署名（推荐）

```
数据来源：虚拟城市（AI Virtual City）· 上海仿真数据集样例
© 2026 石家庄甲兵科技有限公司 · CC BY 4.0
https://www.faceabc.com/city/sampledata/
```

### 英文

```
Source: AI Virtual City — Shanghai simulation sample dataset
© 2026 Shijiazhuang Jiabing Technology Co., Ltd. · CC BY 4.0
https://www.faceabc.com/city/sampledata/
```

### BibTeX（论文用）

```bibtex
@misc{virtualcity_sample,
  title        = {AI Virtual City --- Shanghai simulation sample dataset},
  author       = {{Shijiazhuang Jiabing Technology Co., Ltd.}},
  year         = {2026},
  howpublished = {\url{https://www.faceabc.com/city/sampledata/}},
  note         = {Licensed under CC BY 4.0. Simulated data, not observational data.}
}
```

---

## 四、⚠ 三句必须随数据一起给出的话

**无论以何种方式引用、转述或二次分发本包内容，下面三句必须一并出现。**
这不是免责套话——它们是本数据集**唯一诚实的用法边界**：

```
① 本数据全部是【虚构】的。其中的人、姓名、家庭、事件均为程序生成，
   不对应任何真实个人。姓名取自公开姓名库，与真实人物无关联。

② 本数据是【仿真输出】，不是【测量数据】。它是"给定规则下会发生什么"的推演，
   不是"现实中已经发生了什么"的观测记录。

③ 本数据【不能用于预测】。它未与任何真实统计口径对齐过（见 DATA_SPEC.md 的
   "已知限制"）。它可以用于：方法探索、机制演示、合成数据实验、教学。
   它不可以用于：预测某地某政策的效果、作为决策依据、对外声称"模拟显示…"。
```

---

## 五、已知限制（引用前请读）

本项目的完整限制清单见 [`LIMITATIONS.md`](LIMITATIONS.md)（随包提供）。
其中 **L-1（学历存量分布与普查口径的差距，会让"学历 → 行为"类结论的方向出错）**
影响面最大，**引用学历相关结论前请务必先读**。

---

*本文件由虚拟城市建造方维护 · 最后更新 2026-10-15*
