# UI Review Command

审查指定 Blade 模板的 UI 规范符合度。

## 使用方式
```
/form-review <file_path>
/form-review resources/views/patients/index.blade.php
```

## 审查规范

你是医疗系统 UI/UX 专家，请对提供的 Blade 模板代码进行 UI 规范审查。

### 审查维度

#### 1. 页面结构 (20分)
- [ ] 使用 `portlet light bordered` 作为页面容器
- [ ] 使用 `portlet-title` + `caption-subject` 显示页面标题
- [ ] 标题使用 `__()` 国际化

#### 2. 工具栏 (15分)
- [ ] 使用 `table-toolbar` 容器放置操作按钮
- [ ] 主操作按钮唯一，使用 `btn-theme` 类
- [ ] 按钮文本使用 `__()` 国际化
- [ ] 按钮有图标 (`<i class="fa fa-*">`)

#### 3. 筛选区域 (20分)
- [ ] 使用 `filter-area` 容器包裹筛选条件
- [ ] 使用 Bootstrap grid (`row` + `col-md-*`) 布局
- [ ] 高级筛选可折叠 (`#advancedFilters`)
- [ ] 筛选项使用 `__()` 国际化
- [ ] Select2 设置 `language: '{{ app()->getLocale() }}'`

#### 4. 数据表格 (25分)
- [ ] 使用 `table table-striped table-bordered table-hover` 类
- [ ] DataTables 配置 `language: LanguageManager.getDataTableLang()`
- [ ] DataTables 配置 `serverSide: true` 和 `processing: true`
- [ ] 列数 ≤ 9
- [ ] 表头使用 `__()` 国际化
- [ ] 操作列使用 `orderable: false, searchable: false`

#### 5. 删除确认 (10分)
- [ ] 使用 SweetAlert (`swal()`) 进行删除确认
- [ ] 设置 `confirmButtonClass: "btn-danger"`
- [ ] 确认文本使用 `__()` 国际化

#### 6. 模态框表单 (10分)
- [ ] Modal ID 命名为 `{resource}-modal`
- [ ] 包含 `.alert-danger` 错误显示容器
- [ ] 包含隐藏的 `id` 字段用于编辑模式
- [ ] 表单字段 label 和 placeholder 使用 `__()` 国际化
- [ ] 保存按钮使用 `$.LoadingOverlay()` 显示加载状态

### 输出格式

```markdown
## UI 审查报告

**文件**: `$ARGUMENTS`
**总分**: XX/100

### 问题清单

| 严重度 | 位置 | 问题描述 | 修复建议 |
|--------|------|----------|----------|
| 🔴 P0 | L45 | 硬编码文本"患者列表" | 使用 `{{ __('patient.list') }}` |
| 🟡 P1 | L78 | Select2 缺少 language 配置 | 添加 `language: '{{ app()->getLocale() }}'` |
| 🟢 P2 | L92 | 多个 btn-theme 按钮 | 保留一个主按钮，其他改为 btn-default |

### 国际化问题

列出所有硬编码的中文/英文文本及建议的翻译 key：

| 位置 | 硬编码文本 | 建议 Key |
|------|------------|----------|
| L23 | "新增患者" | `patient.add_new` |
| L45 | "确定删除？" | `common.confirm_delete` |

### 快速修复

1. **[P0]** 具体修改建议...
2. **[P1]** 具体修改建议...

### 修复代码示例

\`\`\`blade
{{-- 针对最严重问题的代码修复示例 --}}
\`\`\`
```

### 执行步骤

1. 读取 `$ARGUMENTS` 指定的 Blade 文件
2. 分析 HTML 结构和 CSS 类使用
3. 检查 `@section('js')` 中的 JavaScript 代码
4. 检查所有用户可见文本是否使用 `__()` 或 `@lang()`
5. 按上述维度逐项评分
6. 输出审查报告和修复建议
