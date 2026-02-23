<?php

return [
    // 页面
    'page_title'       => '菜单管理',
    'menu_tree'        => '菜单树',
    'edit_form'        => '编辑菜单项',
    'add_new'          => '新增菜单项',

    // 字段
    'title_key'        => '标题键',
    'title_key_hint'   => '国际化键名，如 menu.patients_list',
    'url'              => 'URL 路径',
    'url_hint'         => '为空表示目录节点',
    'icon'             => '图标',
    'icon_hint'        => 'CSS 图标类，如 icon-users',
    'permission'       => '关联权限',
    'permission_none'  => '无（不限制）',
    'parent'           => '上级菜单',
    'parent_none'      => '顶级菜单',
    'sort_order'       => '排序',
    'is_active'        => '启用',
    'visible_roles'    => '可见角色',

    // 操作
    'save'             => '保存',
    'delete'           => '删除',
    'confirm_delete'   => '确认删除',
    'confirm_delete_msg' => '删除该菜单项及其所有子项？此操作不可撤销。',
    'reorder_success'  => '排序已保存',

    // 消息
    'created'          => '菜单项已创建',
    'updated'          => '菜单项已更新',
    'deleted'          => '菜单项已删除',
    'not_found'        => '菜单项未找到',

    // 预览
    'preview'          => '预览标题',
    'no_selection'     => '请从左侧选择一个菜单项，或点击"新增菜单项"',

    // 角色提示
    'role_config_hint' => '菜单的角色可见性请在角色管理中配置。',
    'go_to_roles'      => '前往角色管理',

    // 树操作
    'expand_all'       => '展开全部',
    'collapse_all'     => '折叠全部',
];
