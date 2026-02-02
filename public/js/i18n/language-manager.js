/**
 * 多语言管理器
 *
 * 特性：
 * 1. 从 Laravel config/app.php 获取 available_locales
 * 2. 支持标准模块和命名空间模块
 * 3. 支持嵌套键和参数替换
 */

var LanguageManager = (function() {

    var currentLang = 'zh-CN';
    var defaultLang = 'zh-CN';

    // 翻译存储
    var translations = {};
    var dataTableTranslations = {};
    var sweetAlertTranslations = {};

    // 已加载的模块
    var loadedModules = {};

    // 支持的语言列表（从 Laravel 获取）
    var supportedLanguages = {};

    var initialized = false;

    /**
     * 深度合并对象
     */
    function deepMerge(target, source) {
        for (var key in source) {
            if (source.hasOwnProperty(key)) {
                if (typeof source[key] === 'object' && source[key] !== null && !Array.isArray(source[key])) {
                    if (!target[key]) target[key] = {};
                    deepMerge(target[key], source[key]);
                } else {
                    target[key] = source[key];
                }
            }
        }
        return target;
    }

    /**
     * 获取嵌套值
     */
    function getNestedValue(obj, path) {
        var keys = path.split('.');
        var value = obj;
        for (var i = 0; i < keys.length; i++) {
            if (value && typeof value === 'object' && keys[i] in value) {
                value = value[keys[i]];
            } else {
                return undefined;
            }
        }
        return value;
    }

    /**
     * 解析翻译键（支持命名空间）
     */
    function parseTransKey(key) {
        var result = { namespace: null, module: null, key: key, fullModule: null };

        var nsIndex = key.indexOf('::');
        if (nsIndex !== -1) {
            result.namespace = key.substring(0, nsIndex);
            key = key.substring(nsIndex + 2);
        }

        var dotIndex = key.indexOf('.');
        if (dotIndex !== -1) {
            result.module = key.substring(0, dotIndex);
            result.key = key.substring(dotIndex + 1);
        } else {
            result.key = key;
        }

        if (result.namespace && result.module) {
            result.fullModule = result.namespace + '::' + result.module;
        } else if (result.module) {
            result.fullModule = result.module;
        }

        return result;
    }

    /**
     * 确保语言存储已初始化
     */
    function ensureLangStorage(lang) {
        if (!translations[lang]) translations[lang] = {};
        if (!dataTableTranslations[lang]) dataTableTranslations[lang] = {};
        if (!sweetAlertTranslations[lang]) sweetAlertTranslations[lang] = {};
    }

    return {

        /**
         * 初始化
         *
         * @param {object} options
         * @param {object} options.availableLocales - 从 Laravel 传入的语言列表
         * @param {string} options.currentLocale - 当前语言
         * @param {string} options.defaultLocale - 默认语言
         *
         * 使用方式（在 Blade 中）：
         * LanguageManager.init({
         *     availableLocales: @json(config('app.available_locales')),
         *     currentLocale: '{{ app()->getLocale() }}',
         *     defaultLocale: '{{ config('app.fallback_locale') }}'
         * });
         */
        init: function(options) {
            if (initialized) return this;

            options = options || {};

            // 从 Laravel 获取支持的语言列表
            if (options.availableLocales) {
                supportedLanguages = options.availableLocales;
            } else {
                // 默认值
                supportedLanguages = { 'en': 'English', 'zh-CN': '简体中文' };
            }

            // 初始化各语言的存储
            for (var lang in supportedLanguages) {
                ensureLangStorage(lang);
            }

            // 设置默认语言
            if (options.defaultLocale) {
                defaultLang = options.defaultLocale;
            }

            // 设置当前语言
            if (options.currentLocale && supportedLanguages[options.currentLocale]) {
                currentLang = options.currentLocale;
            } else {
                currentLang = this.detectLanguage();
            }

            // 加载全局 JS 语言包
            this.loadGlobalLanguagePacks();

            initialized = true;

            return this;
        },

        /**
         * 检测语言
         */
        detectLanguage: function() {
            // 1. HTML lang 属性
            var htmlLang = document.documentElement.lang;
            if (htmlLang && supportedLanguages[htmlLang]) return htmlLang;

            // 2. localStorage
            var savedLang = localStorage.getItem('app_language');
            if (savedLang && supportedLanguages[savedLang]) return savedLang;

            // 3. 浏览器语言
            var browserLang = navigator.language || navigator.userLanguage;
            if (supportedLanguages[browserLang]) return browserLang;

            // 4. 浏览器语言前缀匹配
            var prefix = browserLang.split('-')[0];
            for (var lang in supportedLanguages) {
                if (lang.indexOf(prefix) === 0 || lang === prefix) {
                    return lang;
                }
            }

            // 5. 返回默认语言
            return defaultLang;
        },

        /**
         * 加载全局 JS 语言包
         */
        loadGlobalLanguagePacks: function() {
            // 动态检测并加载各语言的全局变量
            for (var lang in supportedLanguages) {
                var langKey = lang.replace('-', '_'); // zh-CN -> zh_CN

                // 通用翻译: lang_zh_CN, lang_en
                var transVar = 'lang_' + langKey;
                if (typeof window[transVar] !== 'undefined') {
                    ensureLangStorage(lang);
                    deepMerge(translations[lang], window[transVar]);
                }

                // DataTables: dataTablesLang_zh_CN, dataTablesLang_en
                var dtVar = 'dataTablesLang_' + langKey;
                if (typeof window[dtVar] !== 'undefined') {
                    ensureLangStorage(lang);
                    deepMerge(dataTableTranslations[lang], window[dtVar]);
                }

                // SweetAlert: sweetAlertLang_zh_CN, sweetAlertLang_en
                var saVar = 'sweetAlertLang_' + langKey;
                if (typeof window[saVar] !== 'undefined') {
                    ensureLangStorage(lang);
                    deepMerge(sweetAlertTranslations[lang], window[saVar]);
                }
            }
        },

        /**
         * 从 Blade 加载 PHP 翻译
         */
        loadFromPHP: function(data, namespace) {
            if (!data) return this;

            ensureLangStorage(currentLang);

            if (namespace) {
                if (!translations[currentLang][namespace]) {
                    translations[currentLang][namespace] = {};
                }
                deepMerge(translations[currentLang][namespace], data);
                loadedModules[currentLang + ':' + namespace] = true;
            } else {
                deepMerge(translations[currentLang], data);
            }

            return this;
        },

        /**
         * 批量加载多个模块
         */
        loadAllFromPHP: function(modulesData) {
            for (var namespace in modulesData) {
                if (modulesData.hasOwnProperty(namespace)) {
                    this.loadFromPHP(modulesData[namespace], namespace);
                }
            }
            return this;
        },

        /**
         * 注册模块翻译
         */
        registerModule: function(moduleName, zhCN, en) {
            if (zhCN) {
                ensureLangStorage('zh-CN');
                if (!translations['zh-CN'][moduleName]) translations['zh-CN'][moduleName] = {};
                deepMerge(translations['zh-CN'][moduleName], zhCN);
            }
            if (en) {
                ensureLangStorage('en');
                if (!translations['en'][moduleName]) translations['en'][moduleName] = {};
                deepMerge(translations['en'][moduleName], en);
            }
            return this;
        },

        /**
         * 注册指定语言的模块翻译
         *
         * 示例:
         * LanguageManager.registerModuleForLang('zh-TW', 'patient', { name: '患者姓名' });
         */
        registerModuleForLang: function(lang, moduleName, data) {
            ensureLangStorage(lang);
            if (!translations[lang][moduleName]) translations[lang][moduleName] = {};
            deepMerge(translations[lang][moduleName], data);
            return this;
        },

        /**
         * 获取翻译
         */
        trans: function(key, defaultValueOrParams, params) {
            var defaultValue = key;

            if (typeof defaultValueOrParams === 'object') {
                params = defaultValueOrParams;
            } else if (typeof defaultValueOrParams === 'string') {
                defaultValue = defaultValueOrParams;
            }

            var langTranslations = translations[currentLang] || translations[defaultLang] || {};
            var text;

            var parsed = parseTransKey(key);

            if (parsed.fullModule) {
                var moduleData = langTranslations[parsed.fullModule];
                if (moduleData) {
                    text = getNestedValue(moduleData, parsed.key);
                    if (text === undefined) {
                        text = moduleData[parsed.key];
                    }
                }
            } else {
                text = langTranslations[key];
                if (text === undefined) {
                    text = getNestedValue(langTranslations, key);
                }
            }

            if (text === undefined) {
                text = defaultValue;
            }

            // 参数替换
            if (params && typeof params === 'object') {
                for (var k in params) {
                    if (params.hasOwnProperty(k)) {
                        text = text.replace(new RegExp(':' + k, 'gi'), params[k]);
                        text = text.replace(new RegExp('\\{' + k + '\\}', 'gi'), params[k]);
                    }
                }
            }

            return text;
        },

        /**
         * 检查翻译是否存在
         */
        has: function(key) {
            var result = this.trans(key, '___NOT_FOUND___');
            return result !== '___NOT_FOUND___' && result !== key;
        },

        /**
         * 获取整个模块的翻译
         */
        getModule: function(moduleName) {
            var langTranslations = translations[currentLang] || {};
            return langTranslations[moduleName] || {};
        },

        /**
         * 切换语言
         */
        switchLanguage: function(lang, reload) {
            lang = this.normalizeLocale(lang);

            if (!supportedLanguages[lang]) {
                console.error('Unsupported language:', lang, 'Available:', Object.keys(supportedLanguages));
                return false;
            }

            localStorage.setItem('app_language', lang);

            $.ajax({
                type: 'POST',
                url: '/set-locale',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), locale: lang },
                complete: function() {
                    if (reload !== false) location.reload();
                }
            });
            return true;
        },

        /**
         * 规范化语言代码
         */
        normalizeLocale: function(locale) {
            // 检查是否直接匹配
            if (supportedLanguages[locale]) {
                return locale;
            }

            // 常见映射
            var map = {
                'zh': 'zh-CN',
                'zh-cn': 'zh-CN',
                'zh-hans': 'zh-CN',
                'zh-tw': 'zh-TW',
                'zh-hant': 'zh-TW',
                'en-us': 'en',
                'en-gb': 'en'
            };

            var normalized = map[locale.toLowerCase()];
            if (normalized && supportedLanguages[normalized]) {
                return normalized;
            }

            // 前缀匹配
            var prefix = locale.split('-')[0].toLowerCase();
            for (var lang in supportedLanguages) {
                if (lang.toLowerCase().indexOf(prefix) === 0) {
                    return lang;
                }
            }

            return locale;
        },

        /**
         * 添加新的支持语言（运行时动态添加）
         */
        addSupportedLanguage: function(code, name) {
            supportedLanguages[code] = name;
            ensureLangStorage(code);
            return this;
        },

        /**
         * Join surname and othername with locale-aware separator.
         */
        joinName: function(surname, othername) {
            if (currentLang === 'zh-CN') {
                return (surname || '') + (othername || '');
            }
            return (surname || '') + ' ' + (othername || '');
        },

        // Getters
        getCurrentLanguage: function() { return currentLang; },
        getDefaultLanguage: function() { return defaultLang; },
        getSupportedLanguages: function() { return supportedLanguages; },
        getAllTranslations: function() { return translations; },
        getLoadedModules: function() { return Object.keys(loadedModules); },
        getDataTableLang: function() { return dataTableTranslations[currentLang] || dataTableTranslations[defaultLang] || {}; },
        getSweetAlertLang: function() { return sweetAlertTranslations[currentLang] || sweetAlertTranslations[defaultLang] || {}; },

        /**
         * 创建语言切换下拉框（自动使用 config 中的语言列表）
         */
        createLanguageSelector: function(selectId, className) {
            var html = '<select id="' + (selectId || 'language-selector') + '" class="' + (className || 'form-control input-sm') + '">';
            for (var code in supportedLanguages) {
                html += '<option value="' + code + '"' + (code === currentLang ? ' selected' : '') + '>' + supportedLanguages[code] + '</option>';
            }
            return html + '</select>';
        },

        /**
         * 绑定语言切换事件
         */
        bindLanguageSelector: function(selectId) {
            var self = this;
            $(document).on('change', '#' + (selectId || 'language-selector'), function() {
                self.switchLanguage($(this).val());
            });
        }
    };
})();

/**
 * 全局翻译函数
 */
function trans(key, defaultValueOrParams, params) {
    return LanguageManager.trans(key, defaultValueOrParams, params);
}

function transHas(key) {
    return LanguageManager.has(key);
}