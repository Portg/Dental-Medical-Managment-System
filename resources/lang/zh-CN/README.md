# 中文国际化文件说明

本目录包含了牙科/医疗管理系统的中文（简体）国际化翻译文件。

## 文件结构

```
resources/lang/zh-CN/
├── auth.php          # 认证相关翻译
├── validation.php    # 表单验证翻译
├── passwords.php     # 密码重置翻译
├── pagination.php    # 分页翻译
├── common.php        # 通用UI元素翻译
├── menu.php          # 导航菜单翻译
├── patient.php       # 患者管理翻译
├── appointment.php   # 预约管理翻译
├── medical.php       # 医疗记录翻译
├── financial.php     # 财务/发票翻译
├── report.php        # 报表翻译
├── settings.php      # 设置和配置翻译
├── module.php        # 模块特定翻译
└── messages.php      # 控制器消息翻译
```

## JavaScript 翻译文件

```
public/js/
└── lang-zh-CN.js     # JavaScript翻译支持
```

## 使用方法

### 在 Blade 模板中使用

#### 使用 `__()` 帮助函数：
```php
{{ __('common.save') }}
{{ __('patient.add_patient') }}
{{ __('appointment.book_appointment') }}
```

#### 使用 `@lang` 指令：
```php
@lang('common.welcome')
@lang('menu.dashboard')
```

#### 使用 `trans()` 函数：
```php
{{ trans('common.submit') }}
{{ trans('patient.patient_name') }}
```

### 在控制器中使用

```php
// 返回翻译消息
return response()->json([
    'message' => __('messages.patient_added_successfully')
]);

// 使用 flash 消息
return redirect()->back()->with('success', __('messages.operation_successful'));

// 在验证中使用
$messages = [
    'required' => __('validation.required'),
    'email' => __('validation.email')
];
```

### 在 JavaScript 中使用

首先在布局文件中引入 JavaScript 翻译文件：

```html
<script src="{{ asset('js/lang-zh-CN.js') }}"></script>
```

然后在 JavaScript 代码中使用：

```javascript
// 使用 trans() 函数
alert(trans('confirm_delete'));

// 使用确认对话框
confirmDelete(function() {
    // 删除操作
});

// 显示成功消息
showSuccess(trans('saved_successfully'));

// 显示错误消息
showError(trans('error_occurred'));

// DataTables 会自动使用中文语言
$('#myTable').DataTable({
    // DataTables 配置...
    // 语言设置会自动应用
});
```

### 配置默认语言

在 `config/app.php` 中设置默认语言：

```php
'locale' => 'zh-CN',
'fallback_locale' => 'en',
```

### 动态切换语言

在控制器中：

```php
// 临时切换语言
App::setLocale('zh-CN');

// 或者在中间件中设置
public function handle($request, Closure $next)
{
    $locale = $request->user()->locale ?? 'zh-CN';
    App::setLocale($locale);
    return $next($request);
}
```

## 翻译文件内容说明

### auth.php
包含登录、注册、密码重置等认证相关的翻译。

### validation.php
包含Laravel表单验证的所有错误消息翻译。

### common.php
包含通用的UI元素翻译，如：
- 操作按钮（保存、取消、删除等）
- 状态（活跃、待处理、已完成等）
- 常用字段（姓名、日期、金额等）
- 通用消息

### menu.php
包含所有导航菜单项的翻译。

### patient.php
患者管理模块的完整翻译，包括：
- 患者信息字段
- 联系信息
- 紧急联系人
- 保险信息
- 医疗信息
- 操作和消息

### appointment.php
预约管理模块的翻译，包括：
- 预约类型和状态
- 时间段
- 在线预约
- 提醒和通知

### medical.php
医疗记录模块的翻译，包括：
- 病史
- 过敏史
- 慢性病
- 手术史
- 处方
- 检验结果
- 生命体征

### financial.php
财务管理模块的翻译，包括：
- 发票和报价单
- 付款方式和状态
- 收据
- 费用管理
- 保险理赔

### report.php
报表模块的翻译，包括各种报表类型和统计数据。

### settings.php
系统设置的翻译，包括：
- 诊所设置
- 用户管理
- 角色权限
- 邮件和短信设置
- 备份恢复

### module.php
各个角色模块的翻译（医生、护士、前台、药剂师等）。

### messages.php
控制器返回的消息翻译，包括成功、错误、警告等各类消息。

## JavaScript 翻译功能

`lang-zh-CN.js` 提供了以下功能：

1. **翻译对象** (`lang_zh_CN`): 包含所有JavaScript需要的翻译文本
2. **DataTables 语言** (`dataTablesLang_zh_CN`): DataTables 表格的中文翻译
3. **SweetAlert 按钮** (`sweetAlertLang_zh_CN`): SweetAlert 对话框按钮的中文文本
4. **帮助函数**:
   - `trans(key, defaultValue)`: 获取翻译文本
   - `confirmAction(message, callback)`: 显示确认对话框
   - `confirmDelete(callback, message)`: 显示删除确认对话框
   - `showSuccess(message)`: 显示成功消息
   - `showError(message)`: 显示错误消息
   - `showWarning(message)`: 显示警告消息
   - `showInfo(message)`: 显示信息消息

## 添加新的翻译

如果需要添加新的翻译：

1. 在相应的 PHP 文件中添加新的键值对
2. 在 JavaScript 文件中添加对应的翻译（如果需要在前端使用）
3. 在视图或控制器中使用新添加的翻译键

例如：

```php
// 在 resources/lang/zh-CN/patient.php 中添加
'new_key' => '新的翻译文本',

// 在视图中使用
{{ __('patient.new_key') }}
```

## 注意事项

1. 所有翻译键使用小写字母和下划线
2. 保持翻译简洁明了
3. 确保翻译文本符合中文语言习惯
4. 对于专业术语，使用标准的医疗行业术语
5. 定期检查和更新翻译内容

## 贡献

如果发现翻译错误或需要改进的地方，请提交 issue 或 pull request。

## 版本

- 创建日期: 2025-12-01
- Laravel 版本: 5.8+
- 语言: 简体中文 (zh-CN)
