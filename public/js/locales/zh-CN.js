/**
 * 中文（简体）翻译文件
 */

const zhCN = {
    // 通用文本
    common: {
        hello: '你好',
        welcome: '欢迎',
        save: '保存',
        cancel: '取消',
        delete: '删除',
        edit: '编辑',
        search: '搜索',
        loading: '加载中...',
        confirm: '确认',
        back: '返回',
        next: '下一步',
        submit: '提交',
        close: '关闭'
    },

    // 用户相关
    user: {
        login: '登录',
        logout: '退出登录',
        register: '注册',
        profile: '个人资料',
        username: '用户名',
        password: '密码',
        email: '邮箱',
        phone: '电话',
        greeting: '你好，{name}！',
        welcome_message: '欢迎回来，{name}！你已经离线 {days} 天了。'
    },

    // 牙科/医疗相关
    dental: {
        appointment: '预约',
        patient: '患者',
        doctor: '医生',
        treatment: '治疗',
        diagnosis: '诊断',
        prescription: '处方',
        medical_history: '病历',
        tooth_number: '牙位号码',
        treatment_plan: '治疗计划'
    },

    // 预约管理
    appointment: {
        title: '预约管理',
        new_appointment: '新建预约',
        view_appointment: '查看预约',
        edit_appointment: '编辑预约',
        cancel_appointment: '取消预约',
        appointment_date: '预约日期',
        appointment_time: '预约时间',
        status: {
            pending: '待确认',
            confirmed: '已确认',
            completed: '已完成',
            cancelled: '已取消'
        }
    },

    // 表单验证
    validation: {
        required: '此字段为必填项',
        email_invalid: '请输入有效的邮箱地址',
        password_min_length: '密码长度至少为 {min} 个字符',
        phone_invalid: '请输入有效的电话号码',
        date_invalid: '请输入有效的日期'
    },

    // 消息提示
    message: {
        success: '操作成功！',
        error: '操作失败，请重试。',
        save_success: '保存成功！',
        delete_success: '删除成功！',
        delete_confirm: '确定要删除吗？此操作无法撤销。',
        network_error: '网络错误，请检查您的网络连接。',
        session_expired: '会话已过期，请重新登录。'
    },

    // 复数形式示例
    items: {
        patient_count: '没有患者 | {count} 个患者 | {count} 个患者',
        appointment_count: '没有预约 | {count} 个预约 | {count} 个预约',
        notification_count: '没有新通知 | {count} 条新通知 | {count} 条新通知'
    },

    // 日期和时间
    datetime: {
        today: '今天',
        yesterday: '昨天',
        tomorrow: '明天',
        this_week: '本周',
        last_week: '上周',
        this_month: '本月',
        last_month: '上月',
        morning: '上午',
        afternoon: '下午',
        evening: '晚上'
    }
};

// 导出
if (typeof module !== 'undefined' && module.exports) {
    module.exports = zhCN;
}
if (typeof window !== 'undefined') {
    window.i18nMessages = window.i18nMessages || {};
    window.i18nMessages['zh-CN'] = zhCN;
}
