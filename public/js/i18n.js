/**
 * i18n.js - 简单的国际化（多语言）库
 * 支持动态语言切换、变量插值、复数形式等功能
 */

class I18n {
    constructor(options = {}) {
        this.locale = options.locale || 'zh-CN'; // 默认语言
        this.fallbackLocale = options.fallbackLocale || 'en'; // 回退语言
        this.messages = options.messages || {}; // 翻译消息
        this.missingTranslations = new Set(); // 记录缺失的翻译
    }

    /**
     * 设置当前语言
     * @param {string} locale - 语言代码，如 'zh-CN', 'en'
     */
    setLocale(locale) {
        if (this.messages[locale]) {
            this.locale = locale;
            this.emit('localeChanged', locale);
            return true;
        }
        console.warn(`Locale '${locale}' not found`);
        return false;
    }

    /**
     * 获取当前语言
     * @returns {string} 当前语言代码
     */
    getLocale() {
        return this.locale;
    }

    /**
     * 添加语言翻译
     * @param {string} locale - 语言代码
     * @param {object} messages - 翻译消息对象
     */
    addMessages(locale, messages) {
        if (!this.messages[locale]) {
            this.messages[locale] = {};
        }
        this.messages[locale] = this.deepMerge(this.messages[locale], messages);
    }

    /**
     * 翻译文本
     * @param {string} key - 翻译键，支持点号分隔的嵌套键，如 'user.name'
     * @param {object} params - 替换参数，如 {name: 'John'}
     * @returns {string} 翻译后的文本
     */
    t(key, params = {}) {
        let message = this.getMessage(key, this.locale);

        // 如果找不到，尝试使用回退语言
        if (message === null) {
            message = this.getMessage(key, this.fallbackLocale);
        }

        // 如果还是找不到，返回键名
        if (message === null) {
            this.missingTranslations.add(`${this.locale}.${key}`);
            console.warn(`Translation missing: ${this.locale}.${key}`);
            return key;
        }

        // 替换参数
        return this.interpolate(message, params);
    }

    /**
     * 获取翻译消息
     * @param {string} key - 翻译键
     * @param {string} locale - 语言代码
     * @returns {string|null} 翻译消息
     */
    getMessage(key, locale) {
        const keys = key.split('.');
        let message = this.messages[locale];

        for (const k of keys) {
            if (message && typeof message === 'object' && k in message) {
                message = message[k];
            } else {
                return null;
            }
        }

        return typeof message === 'string' ? message : null;
    }

    /**
     * 变量插值
     * @param {string} message - 翻译消息
     * @param {object} params - 参数对象
     * @returns {string} 替换后的消息
     */
    interpolate(message, params) {
        return message.replace(/\{(\w+)\}/g, (match, key) => {
            return params.hasOwnProperty(key) ? params[key] : match;
        });
    }

    /**
     * 复数形式处理
     * @param {string} key - 翻译键
     * @param {number} count - 数量
     * @param {object} params - 其他参数
     * @returns {string} 翻译后的文本
     */
    tc(key, count, params = {}) {
        const message = this.getMessage(key, this.locale);

        if (message === null) {
            return this.t(key, { ...params, count });
        }

        // 支持简单的复数规则: "item | items"
        const parts = message.split('|').map(s => s.trim());

        let selectedMessage;
        if (parts.length === 1) {
            selectedMessage = parts[0];
        } else if (parts.length === 2) {
            // 二元形式: 单数 | 复数
            selectedMessage = count === 1 ? parts[0] : parts[1];
        } else if (parts.length === 3) {
            // 三元形式: 0 | 单数 | 复数
            if (count === 0) selectedMessage = parts[0];
            else if (count === 1) selectedMessage = parts[1];
            else selectedMessage = parts[2];
        } else {
            selectedMessage = parts[0];
        }

        return this.interpolate(selectedMessage, { ...params, count });
    }

    /**
     * 深度合并对象
     */
    deepMerge(target, source) {
        const result = { ...target };

        for (const key in source) {
            if (source.hasOwnProperty(key)) {
                if (source[key] instanceof Object && key in target) {
                    result[key] = this.deepMerge(target[key], source[key]);
                } else {
                    result[key] = source[key];
                }
            }
        }

        return result;
    }

    /**
     * 事件监听
     */
    on(event, callback) {
        if (!this.listeners) {
            this.listeners = {};
        }
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    }

    /**
     * 触发事件
     */
    emit(event, data) {
        if (this.listeners && this.listeners[event]) {
            this.listeners[event].forEach(callback => callback(data));
        }
    }

    /**
     * 获取缺失的翻译
     */
    getMissingTranslations() {
        return Array.from(this.missingTranslations);
    }

    /**
     * 格式化日期（简单实现）
     * @param {Date|string} date - 日期
     * @param {string} format - 格式，如 'short', 'long'
     * @returns {string} 格式化后的日期
     */
    d(date, format = 'short') {
        const dateObj = date instanceof Date ? date : new Date(date);
        const options = format === 'long'
            ? { year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: '2-digit', day: '2-digit' };

        return dateObj.toLocaleDateString(this.locale, options);
    }

    /**
     * 格式化数字（简单实现）
     * @param {number} number - 数字
     * @param {object} options - 格式选项
     * @returns {string} 格式化后的数字
     */
    n(number, options = {}) {
        return number.toLocaleString(this.locale, options);
    }

    /**
     * 判断某个翻译键是否存在
     * @param {string} key - 翻译键
     * @param {string} locale - 语言代码（可选）
     * @returns {boolean}
     */
    te(key, locale = null) {
        const targetLocale = locale || this.locale;
        return this.getMessage(key, targetLocale) !== null;
    }
}

// 导出为全局变量（浏览器环境）
if (typeof window !== 'undefined') {
    window.I18n = I18n;
}

// 支持 CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = I18n;
}

// 支持 AMD
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return I18n;
    });
}
