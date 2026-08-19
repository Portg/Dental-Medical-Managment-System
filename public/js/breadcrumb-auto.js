/**
 * 自动面包屑生成器 + 侧边栏菜单激活
 * 根据当前URL和侧边栏菜单自动更新面包屑
 * Layout4 sidebar version
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        updateBreadcrumb();
        updateActiveMenu();

        // Reveal breadcrumb area (hidden initially to prevent "工作台" flash)
        var pageHead = document.querySelector('.page-head');
        if (pageHead) {
            pageHead.style.visibility = 'visible';
        }
    });

    /**
     * 更新面包屑
     */
    function updateBreadcrumb() {
        var currentPath = normalizePath(window.location.pathname);
        var breadcrumbContainer = document.querySelector('.page-breadcrumb');
        var pageHead = document.querySelector('.page-head');

        if (!breadcrumbContainer) return;

        // 查找当前激活的菜单项
        var menuInfo = findActiveMenuItem(currentPath);

        if (menuInfo) {
            // 构建面包屑HTML
            var breadcrumbHtml = '';

            // 首页图标
            breadcrumbHtml += '<li class="home-icon"><a href="' + getBaseUrl() + '/home"><i class="icon-home"></i></a></li>';
            breadcrumbHtml += '<li class="separator"></li>';

            // 一级菜单（如果有）
            if (menuInfo.parentName) {
                breadcrumbHtml += '<li><a href="' + (menuInfo.parentUrl || '#') + '">' + menuInfo.parentName + '</a></li>';
                breadcrumbHtml += '<li class="separator"></li>';
            }

            var explicitCurrent = pageHead ? (pageHead.getAttribute('data-breadcrumb-current') || '').trim() : '';
            var isSubPage = currentPath !== menuInfo.currentPath;
            var actionName = isSubPage ? (explicitCurrent || inferActionName(currentPath, menuInfo.currentPath, pageHead)) : '';

            // 新增、编辑、详情页先保留其所属菜单，再显示页面动作。
            if (actionName && actionName !== menuInfo.currentName) {
                breadcrumbHtml += '<li><a href="' + menuInfo.currentUrl + '">' + menuInfo.currentName + '</a></li>';
                breadcrumbHtml += '<li class="separator"></li>';
                breadcrumbHtml += '<li class="current">' + actionName + '</li>';
            } else {
                breadcrumbHtml += '<li class="current">' + menuInfo.currentName + '</li>';
            }

            breadcrumbContainer.innerHTML = breadcrumbHtml;
        }
    }

    /**
     * 没有页面显式标题时，为标准资源路由提供通用动作名称。
     */
    function inferActionName(currentPath, menuPath, pageHead) {
        if (!pageHead || currentPath.indexOf(menuPath + '/') !== 0) return '';

        var relativePath = currentPath.substring(menuPath.length).replace(/^\/+|\/+$/g, '');
        var pathSegments = relativePath ? relativePath.split('/') : [];
        var lastSegment = pathSegments.length ? pathSegments[pathSegments.length - 1] : '';

        if (lastSegment === 'create') {
            return pageHead.getAttribute('data-breadcrumb-add') || '';
        }

        if (lastSegment === 'edit') {
            return pageHead.getAttribute('data-breadcrumb-edit') || '';
        }

        return relativePath ? (pageHead.getAttribute('data-breadcrumb-details') || '') : '';
    }

    function normalizePath(path) {
        if (!path || path === '/') return '/';
        return path.replace(/\/+$/, '');
    }

    /**
     * 更新菜单激活状态 (Layout4 sidebar)
     */
    function updateActiveMenu() {
        var currentPath = normalizePath(window.location.pathname);

        // 移除所有现有的 active / open 类
        document.querySelectorAll('.page-sidebar-menu > li.nav-item').forEach(function(li) {
            li.classList.remove('active', 'open');
        });
        document.querySelectorAll('.page-sidebar-menu .sub-menu li.nav-item').forEach(function(li) {
            li.classList.remove('active');
        });

        // 查找并激活当前菜单项
        var allLinks = document.querySelectorAll('.page-sidebar-menu a.nav-link[href]');
        var bestMatch = null;
        var bestMatchLength = 0;

        allLinks.forEach(function(link) {
            var href = link.getAttribute('href');
            if (href && href !== 'javascript:;' && href !== '#') {
                var linkPath = normalizePath(new URL(href, window.location.origin).pathname);

                // 精确匹配或前缀匹配
                if (pathMatches(currentPath, linkPath)) {
                    if (linkPath.length > bestMatchLength) {
                        bestMatchLength = linkPath.length;
                        bestMatch = link;
                    }
                }
            }
        });

        if (bestMatch) {
            // 激活当前链接的 li
            var li = bestMatch.closest('li.nav-item');
            if (li) {
                li.classList.add('active');

                // 如果是子菜单项，也激活并展开父级菜单
                var parentSubMenu = li.closest('.sub-menu');
                if (parentSubMenu) {
                    var parentNavItem = parentSubMenu.closest('li.nav-item');
                    if (parentNavItem) {
                        parentNavItem.classList.add('active', 'open');
                    }
                }
            }
        }
    }

    /**
     * 查找当前激活的菜单项信息 (Layout4 sidebar)
     */
    function findActiveMenuItem(currentPath) {
        var bestMatch = null;
        var bestMatchLength = 0;

        // 遍历所有顶级菜单项
        var menuItems = document.querySelectorAll('.page-sidebar-menu > li.nav-item');

        menuItems.forEach(function(menuLi) {
            var parentLink = menuLi.querySelector(':scope > a.nav-link');
            var parentName = '';
            if (parentLink) {
                var titleSpan = parentLink.querySelector('.title');
                parentName = titleSpan ? titleSpan.textContent.trim() : parentLink.textContent.trim();
            }

            var parentHref = parentLink ? parentLink.getAttribute('href') : null;
            var parentUrl = (parentHref && parentHref !== 'javascript:;' && parentHref !== '#') ? parentHref : null;

            // 检查是否有子菜单
            var subMenu = menuLi.querySelector('.sub-menu');

            if (!subMenu) {
                // 直接链接菜单项
                if (parentUrl) {
                    var linkPath = normalizePath(new URL(parentUrl, window.location.origin).pathname);
                    if (pathMatches(currentPath, linkPath)) {
                        if (linkPath.length > bestMatchLength) {
                            bestMatchLength = linkPath.length;
                            bestMatch = {
                                parentName: null,
                                parentUrl: null,
                                currentName: parentName,
                                currentUrl: parentUrl,
                                currentPath: linkPath
                            };
                        }
                    }
                }
            } else {
                // 有子菜单
                var subItems = subMenu.querySelectorAll('li.nav-item > a.nav-link');

                subItems.forEach(function(subLink) {
                    var subHref = subLink.getAttribute('href');
                    if (subHref && subHref !== 'javascript:;' && subHref !== '#') {
                        var subPath = normalizePath(new URL(subHref, window.location.origin).pathname);

                        if (pathMatches(currentPath, subPath)) {
                            if (subPath.length > bestMatchLength) {
                                bestMatchLength = subPath.length;
                                var subTitleSpan = subLink.querySelector('.title');
                                var subName = subTitleSpan ? subTitleSpan.textContent.trim() : subLink.textContent.trim();
                                bestMatch = {
                                    parentName: parentName,
                                    parentUrl: parentUrl,
                                    currentName: subName,
                                    currentUrl: subHref,
                                    currentPath: subPath
                                };
                            }
                        }
                    }
                });
            }
        });

        return bestMatch;
    }

    function pathMatches(currentPath, menuPath) {
        return currentPath === menuPath ||
            (menuPath !== '/' && currentPath.indexOf(menuPath + '/') === 0);
    }

    /**
     * 获取基础URL
     */
    function getBaseUrl() {
        return window.location.origin;
    }

})();
