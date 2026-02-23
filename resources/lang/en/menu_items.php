<?php

return [
    // Page
    'page_title'       => 'Menu Management',
    'menu_tree'        => 'Menu Tree',
    'edit_form'        => 'Edit Menu Item',
    'add_new'          => 'Add Menu Item',

    // Fields
    'title_key'        => 'Title Key',
    'title_key_hint'   => 'i18n key, e.g. menu.patients_list',
    'url'              => 'URL Path',
    'url_hint'         => 'Leave empty for directory node',
    'icon'             => 'Icon',
    'icon_hint'        => 'CSS icon class, e.g. icon-users',
    'permission'       => 'Permission',
    'permission_none'  => 'None (unrestricted)',
    'parent'           => 'Parent Menu',
    'parent_none'      => 'Top Level',
    'sort_order'       => 'Sort Order',
    'is_active'        => 'Enabled',
    'visible_roles'    => 'Visible Roles',

    // Actions
    'save'             => 'Save',
    'delete'           => 'Delete',
    'confirm_delete'   => 'Confirm Delete',
    'confirm_delete_msg' => 'Delete this menu item and all its children? This cannot be undone.',
    'reorder_success'  => 'Order saved',

    // Messages
    'created'          => 'Menu item created',
    'updated'          => 'Menu item updated',
    'deleted'          => 'Menu item deleted',
    'not_found'        => 'Menu item not found',

    // Preview
    'preview'          => 'Title Preview',
    'no_selection'     => 'Select a menu item from the tree, or click "Add Menu Item"',

    // Role hint
    'role_config_hint' => 'Menu visibility by role is configured in Role Management.',
    'go_to_roles'      => 'Go to Roles',

    // Tree actions
    'expand_all'       => 'Expand All',
    'collapse_all'     => 'Collapse All',
];
