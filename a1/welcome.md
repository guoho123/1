# 欢迎使用项目系统

这是一篇示例文章。直接编辑 `a1/welcome.md` 即可修改正文。

## 如何新增文章

1. 在 `a1/` 文件夹里放入一个新的 `.md` 文件，例如 `my-post.md`
2. 打开 `a1/manifest.json`，在数组里加一条记录：

```json
{
  "title": "我的新项目",
  "file": "my-post.md",
  "date": "2026-08-20",
  "summary": "摘要，会显示在项目卡片上",
  "cover": "img/xxx.png"
}
```

3. 刷新项目主页，新项目会自动出现在列表里，点击即可查看详情。   

## 支持的 Markdown 语法

正文使用标准 Markdown 语法，由 `marked.js` 渲染：

- **加粗**、*斜体*、`行内代码`
- 列表（有序 / 无序）
- 引用块
- 表格
- 代码块

```javascript
console.log("hello world");
```

> 引用示例：保持简单，先把能用的做出来。

| 字段 | 说明 |
| --- | --- |
| title | 项目标题 |
| file | md 文件名 |
| date | 发布日期 |
| summary | 卡片摘要 |
| cover | 封面图（可选） |

就这么简单，开始写你的个项目吧。
