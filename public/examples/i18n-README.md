# i18n.js 使用指南

## 简介

`i18n.js` 是一个轻量级的国际化（多语言）JavaScript库，用于在网页应用中实现多语言支持。

## 功能特性

- ✅ 支持多语言切换
- ✅ 支持嵌套键（如 `user.name`）
- ✅ 支持变量插值（如 `Hello, {name}!`）
- ✅ 支持复数形式处理
- ✅ 支持日期和数字格式化
- ✅ 支持回退语言
- ✅ 轻量级，无依赖
- ✅ 支持事件监听

---

## 快速开始

### 1. 引入文件

```html
<!-- 引入 i18n.js 核心库 -->
<script src="js/i18n.js"></script>

<!-- 引入语言文件 -->
<script src="js/locales/zh-CN.js"></script>
<script src="js/locales/en.js"></script>
```

### 2. 初始化

```javascript
// 初始化 i18n 实例
const i18n = new I18n({
    locale: 'zh-CN',          // 默认语言
    fallbackLocale: 'en',     // 回退语言
    messages: {
        'zh-CN': zhCN,        // 中文翻译
        'en': en              // 英文翻译
    }
});
```

### 3. 基本使用

```javascript
// 简单翻译
i18n.t('common.hello')  // 输出: "你好"

// 嵌套键翻译
i18n.t('user.profile')  // 输出: "个人资料"

// 变量插值
i18n.t('user.greeting', { name: '张三' })  // 输出: "你好，张三！"
```

---

## API 文档

### 构造函数

```javascript
new I18n(options)
```

**参数:**
- `options.locale` (String): 默认语言代码，如 `'zh-CN'`
- `options.fallbackLocale` (String): 回退语言代码，如 `'en'`
- `options.messages` (Object): 翻译消息对象

### 核心方法

#### 1. `t(key, params)` - 翻译文本

翻译指定键的文本，支持变量插值。

```javascript
// 基础翻译
i18n.t('common.hello')
// 输出: "你好"

// 带参数的翻译
i18n.t('user.greeting', { name: '李四' })
// 输出: "你好，李四！"

// 多个参数
i18n.t('user.welcome_message', { name: '王五', days: 3 })
// 输出: "欢迎回来，王五！你已经离线 3 天了。"
```

**参数:**
- `key` (String): 翻译键，支持点号分隔的嵌套键
- `params` (Object): 可选，用于替换的变量对象

**返回:** (String) 翻译后的文本

---

#### 2. `tc(key, count, params)` - 复数形式翻译

根据数量处理复数形式。

```javascript
// 定义复数规则（在语言文件中）
// items: {
//     patient_count: '没有患者 | {count} 个患者 | {count} 个患者'
// }

i18n.tc('items.patient_count', 0)   // 输出: "没有患者"
i18n.tc('items.patient_count', 1)   // 输出: "1 个患者"
i18n.tc('items.patient_count', 10)  // 输出: "10 个患者"
```

**参数:**
- `key` (String): 翻译键
- `count` (Number): 数量
- `params` (Object): 可选，额外的变量参数

**返回:** (String) 翻译后的文本

**复数格式说明:**
- 单一形式: `"item"`
- 二元形式: `"item | items"` (单数 | 复数)
- 三元形式: `"no items | one item | many items"` (0 | 1 | 多个)

---

#### 3. `setLocale(locale)` - 切换语言

```javascript
i18n.setLocale('en')  // 切换到英文
i18n.setLocale('zh-CN')  // 切换到中文
```

**参数:**
- `locale` (String): 语言代码

**返回:** (Boolean) 成功返回 true，失败返回 false

---

#### 4. `getLocale()` - 获取当前语言

```javascript
const currentLocale = i18n.getLocale()
console.log(currentLocale)  // 输出: "zh-CN"
```

**返回:** (String) 当前语言代码

---

#### 5. `addMessages(locale, messages)` - 添加翻译

动态添加或更新翻译消息。

```javascript
i18n.addMessages('zh-CN', {
    custom: {
        message: '自定义消息'
    }
})
```

**参数:**
- `locale` (String): 语言代码
- `messages` (Object): 翻译消息对象

---

#### 6. `d(date, format)` - 日期格式化

根据当前语言格式化日期。

```javascript
const now = new Date()

i18n.d(now, 'short')  // 输出: "2024/01/15" (中文)
i18n.d(now, 'long')   // 输出: "2024年1月15日" (中文)
```

**参数:**
- `date` (Date|String): 日期对象或日期字符串
- `format` (String): 格式类型，`'short'` 或 `'long'`

**返回:** (String) 格式化后的日期

---

#### 7. `n(number, options)` - 数字格式化

根据当前语言格式化数字。

```javascript
i18n.n(1234567.89)
// 中文输出: "1,234,567.89"
// 英文输出: "1,234,567.89"

// 货币格式
i18n.n(1234567.89, { style: 'currency', currency: 'CNY' })
// 输出: "¥1,234,567.89"
```

**参数:**
- `number` (Number): 要格式化的数字
- `options` (Object): 可选，格式化选项（参考 Intl.NumberFormat）

**返回:** (String) 格式化后的数字

---

#### 8. `te(key, locale)` - 检查翻译是否存在

```javascript
i18n.te('common.hello')      // true
i18n.te('common.notexist')   // false
i18n.te('common.hello', 'en')  // true (检查英文翻译)
```

**参数:**
- `key` (String): 翻译键
- `locale` (String): 可选，指定语言代码

**返回:** (Boolean) 存在返回 true，不存在返回 false

---

#### 9. `on(event, callback)` - 监听事件

```javascript
i18n.on('localeChanged', function(locale) {
    console.log('语言已切换到:', locale)
    // 执行其他操作，如更新页面内容
})
```

**参数:**
- `event` (String): 事件名称，目前支持 `'localeChanged'`
- `callback` (Function): 回调函数

---

## 语言文件结构

语言文件应该是一个包含翻译键值对的对象：

```javascript
const zhCN = {
    // 简单键
    hello: '你好',

    // 嵌套对象
    user: {
        name: '姓名',
        email: '邮箱',
        greeting: '你好，{name}！'  // 支持变量
    },

    // 复数形式
    items: {
        count: '没有项目 | {count} 个项目 | {count} 个项目'
    }
}
```

---

## 实际应用示例

### 示例 1: 表单国际化

```html
<form id="login-form">
    <h2 id="form-title"></h2>
    <label id="label-username"></label>
    <input type="text" id="username">

    <label id="label-password"></label>
    <input type="password" id="password">

    <button type="submit" id="btn-submit"></button>
</form>

<script>
function updateForm() {
    document.getElementById('form-title').textContent = i18n.t('user.login')
    document.getElementById('label-username').textContent = i18n.t('user.username')
    document.getElementById('label-password').textContent = i18n.t('user.password')
    document.getElementById('btn-submit').textContent = i18n.t('common.submit')
}

// 初始化
updateForm()

// 语言切换时更新
i18n.on('localeChanged', updateForm)
</script>
```

### 示例 2: 通知消息

```javascript
function showNotification(type, key, params) {
    const message = i18n.t(key, params)
    alert(message)
}

// 成功消息
showNotification('success', 'message.save_success')
// 输出: "保存成功！"

// 带参数的消息
showNotification('info', 'user.welcome_message', {
    name: '张三',
    days: 5
})
// 输出: "欢迎回来，张三！你已经离线 5 天了。"
```

### 示例 3: 动态列表

```javascript
function displayPatientCount(count) {
    const message = i18n.tc('items.patient_count', count)
    document.getElementById('patient-count').textContent = message
}

displayPatientCount(0)   // "没有患者"
displayPatientCount(1)   // "1 个患者"
displayPatientCount(10)  // "10 个患者"
```

### 示例 4: 语言切换按钮

```html
<div class="language-switcher">
    <button onclick="switchLanguage('zh-CN')">中文</button>
    <button onclick="switchLanguage('en')">English</button>
</div>

<script>
function switchLanguage(locale) {
    if (i18n.setLocale(locale)) {
        // 刷新页面上的所有翻译文本
        updateAllTranslations()
    }
}

function updateAllTranslations() {
    // 更新所有需要翻译的元素
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n')
        element.textContent = i18n.t(key)
    })
}
</script>
```

### 示例 5: 使用 data-i18n 属性

```html
<!-- HTML 标记 -->
<h1 data-i18n="common.welcome"></h1>
<button data-i18n="common.save"></button>
<p data-i18n="user.greeting" data-i18n-params='{"name":"张三"}'></p>

<script>
function translatePage() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n')
        const paramsAttr = element.getAttribute('data-i18n-params')
        const params = paramsAttr ? JSON.parse(paramsAttr) : {}

        element.textContent = i18n.t(key, params)
    })
}

// 页面加载时翻译
window.onload = translatePage

// 语言切换时重新翻译
i18n.on('localeChanged', translatePage)
</script>
```

---

## 最佳实践

### 1. 组织翻译文件

按功能模块组织翻译键：

```javascript
{
    common: { /* 通用文本 */ },
    user: { /* 用户相关 */ },
    appointment: { /* 预约相关 */ },
    validation: { /* 表单验证 */ },
    message: { /* 提示消息 */ }
}
```

### 2. 使用命名空间

```javascript
// 推荐
i18n.t('user.login')
i18n.t('appointment.create')

// 不推荐
i18n.t('login')
i18n.t('create')
```

### 3. 变量命名清晰

```javascript
// 推荐
'Hello, {username}!'

// 不推荐
'Hello, {0}!'
```

### 4. 提供回退语言

始终设置回退语言，避免翻译缺失时显示键名：

```javascript
const i18n = new I18n({
    locale: 'zh-CN',
    fallbackLocale: 'en',  // 重要！
    messages: messages
})
```

### 5. 监听语言变化

```javascript
i18n.on('localeChanged', function(locale) {
    // 更新页面
    updatePageContent()

    // 保存用户偏好
    localStorage.setItem('user-locale', locale)
})
```

---

## 浏览器兼容性

- Chrome ✅
- Firefox ✅
- Safari ✅
- Edge ✅
- IE11 ✅ (需要 Promise polyfill)

---

## 文件结构

```
public/
├── js/
│   ├── i18n.js              # 核心库
│   └── locales/
│       ├── zh-CN.js         # 中文翻译
│       ├── en.js            # 英文翻译
│       └── ...              # 其他语言
└── examples/
    ├── i18n-example.html    # 完整示例
    └── i18n-README.md       # 本文档
```

---

## 常见问题

### Q: 如何添加新语言？

A: 创建新的语言文件并引入：

```javascript
// locales/fr.js
const fr = {
    common: {
        hello: 'Bonjour'
    }
}

// 在 HTML 中引入
<script src="js/locales/fr.js"></script>

// 添加到 i18n
i18n.addMessages('fr', fr)
```

### Q: 如何处理缺失的翻译？

A: i18n.js 会自动使用回退语言。如果回退语言也没有，会返回键名并在控制台输出警告。

```javascript
// 获取所有缺失的翻译
const missing = i18n.getMissingTranslations()
console.log(missing)
```

### Q: 支持从服务器加载翻译吗？

A: 支持，可以通过 AJAX 加载：

```javascript
async function loadTranslations(locale) {
    const response = await fetch(`/api/translations/${locale}`)
    const translations = await response.json()
    i18n.addMessages(locale, translations)
}
```

---

## 许可证

MIT License

---

## 联系与支持

如有问题或建议，请联系开发团队。

---

**查看完整示例:** 打开 `i18n-example.html` 文件在浏览器中查看所有功能演示。
