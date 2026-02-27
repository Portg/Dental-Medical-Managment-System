{{--
    List Page Base Template
    =========================

    Usage:
    @extends('layouts.list-page')

    Required sections:
    - page_title: Page title text
    - table_id: Unique table ID
    - table_headers: Table column headers

    Optional sections:
    - page_css: Additional page-specific CSS
    - page_js: Additional page-specific JavaScript
    - header_actions: Header action buttons (export, add new, etc.)
    - filter_primary: Primary filter controls (search, common filters)
    - filter_advanced: Advanced filter controls (collapsible)
    - empty_icon: Empty state icon class (default: fa-inbox)
    - empty_title: Empty state title
    - empty_desc: Empty state description
    - empty_action: Empty state action button
    - modals: Modal dialogs

    Available variables (pass via @section or set in controller):
    - $pageTitle: Page title
    - $tableId: Table ID
    - $createRoute: Route for create action
    - $exportRoute: Route for export action
--}}

@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    @include('layouts.page_loader')
    {{-- Unified list page styles --}}
    <link rel="stylesheet" href="{{ asset('css/list-page.css') }}">
    {{-- Unified form modal styles --}}
    <link rel="stylesheet" href="{{ asset('css/form-modal.css') }}">

    {{-- Page-specific CSS --}}
    @yield('page_css')
@endsection

@section('content')
<div class="row">
    {{-- Optional Left Sidebar --}}
    @hasSection('left_sidebar')
    <div class="col-md-2">
        @yield('left_sidebar')
    </div>
    @endif

    <div class="{{ View::hasSection('left_sidebar') ? 'col-md-10' : 'col-md-12' }}">
        <div class="portlet light bordered">
            <div class="portlet-body">
                {{-- L1: Page Header --}}
                <div class="page-header-l1">
                    <h1 class="page-title">@yield('page_title')</h1>
                    <div class="header-actions">
                        @section('header_actions')
                            {{-- Default: Export and Add New buttons --}}
                            @if(isset($exportRoute))
                            <button type="button" class="btn btn-default" onclick="exportData()">
                                {{__('common.export')}}
                            </button>
                            @endif
                            @if(isset($createRoute) || View::hasSection('create_action'))
                            <button type="button" class="btn btn-primary" onclick="createRecord()">
                                {{__('common.add_new')}}
                            </button>
                            @endif
                        @show
                    </div>
                </div>

                {{-- L2: Filter Area --}}
                @if(View::hasSection('filter_area') || View::hasSection('filter_primary') || View::hasSection('filter_advanced'))
                <div class="filter-area-l2">
                    @hasSection('filter_area')
                        {{-- Full custom filter area --}}
                        @yield('filter_area')
                    @else
                        {{-- Primary Filters --}}
                        @hasSection('filter_primary')
                        <div class="row filter-row">
                            @yield('filter_primary')

                            {{-- Filter Actions --}}
                            <div class="col-md-4 text-right filter-actions">
                                <button type="button" class="btn btn-default" onclick="clearFilters()">
                                    {{__('common.reset')}}
                                </button>
                                <button type="button" class="btn btn-primary" onclick="doSearch()">
                                    {{__('common.search')}}
                                </button>
                            </div>
                        </div>
                        @endif

                        {{-- Advanced Filters (Collapsible) --}}
                        @hasSection('filter_advanced')
                        <div id="advancedFilters" class="advanced-filters-section" style="display: none;">
                            <div class="row filter-row">
                                @yield('filter_advanced')
                            </div>
                        </div>
                        <div class="advanced-filter-toggle">
                            <button type="button" id="toggleAdvancedFilters" class="btn btn-link advanced-filter-btn">
                                {{__('common.advanced_filter')}}
                            </button>
                        </div>
                        @endif
                    @endif
                </div>
                @endif

                {{-- L3: Data Table --}}
                <table class="table table-hover list-table" id="@yield('table_id', 'data-table')">
                    <thead>
                    <tr>
                        @yield('table_headers')
                    </tr>
                    </thead>
                    <tbody id="@yield('table_id', 'data-table')-tbody">
                    </tbody>
                </table>

                {{-- L4: Empty State --}}
                <div id="emptyState" class="empty-state-container" style="display: none;">
                    <div class="empty-icon">
                        <i class="icon-drawer @yield('empty_icon', '')"></i>
                    </div>
                    <div class="empty-title">
                        @yield('empty_title', __('common.no_data_found'))
                    </div>
                    <div class="empty-desc">
                        @hasSection('empty_desc')
                            @yield('empty_desc')
                        @else
                            @if(isset($createRoute) || View::hasSection('create_action'))
                                {{ __('common.click_add_to_start') }}
                            @else
                                {{ __('common.try_adjust_filters') }}
                            @endif
                        @endif
                    </div>
                    @hasSection('empty_action')
                        @yield('empty_action')
                    @else
                        @if(isset($createRoute) || View::hasSection('create_action'))
                        <button type="button" class="btn btn-primary" onclick="createRecord()">
                            {{__('common.add_new')}}
                        </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div class="loading">
    <i class="icon-refresh" style="font-size: 24px;"></i><br/>
    <span>{{__('common.loading')}}</span>
</div>

{{-- Modal Dialogs --}}
@yield('modals')

@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/DataTableManager.js') }}" type="text/javascript"></script>

<script type="text/javascript">
    // ==========================================================================
    // Base List Page JavaScript
    // ==========================================================================

    /**
     * DataTable instance - to be initialized by child page
     * @type {DataTable}
     */
    var dataTable = null;

    /**
     * Get the table ID
     * @returns {string}
     */
    function getTableId() {
        return '@yield("table_id", "data-table")';
    }

    /**
     * Get the DataTable selector
     * @returns {string}
     */
    function getTableSelector() {
        return '#' + getTableId();
    }

    // ==========================================================================
    // Filter Functions
    // ==========================================================================

    /**
     * Toggle advanced filters visibility
     */
    $('#toggleAdvancedFilters').on('click', function() {
        var $advFilters = $('#advancedFilters');
        var $icon = $(this).find('i');
        if ($advFilters.is(':visible')) {
            $advFilters.slideUp();
            $icon.removeClass('fa-angle-up').addClass('fa-angle-down');
        } else {
            $advFilters.slideDown();
            $icon.removeClass('fa-angle-down').addClass('fa-angle-up');
        }
    });

    /**
     * Perform search - redraw DataTable
     */
    function doSearch() {
        if (dataTable) {
            dataTable.draw(true);
        }
    }

    /**
     * Clear all filters - override in child page for custom behavior
     */
    function clearFilters() {
        // Reset text, date, datetime-local inputs
        $('.filter-area-l2 input[type="text"], .filter-area-l2 input[type="date"], .filter-area-l2 input[type="datetime-local"]').val('');

        // Reset select2
        $('.filter-area-l2 select').val(null).trigger('change');

        // Reset native selects
        $('.filter-area-l2 select:not(.select2)').val('');

        // Call custom clear if defined
        if (typeof clearCustomFilters === 'function') {
            clearCustomFilters();
        }

        // Redraw table
        doSearch();
    }

    /**
     * Debounce helper for search inputs
     * @param {Function} func - Function to debounce
     * @param {number} wait - Debounce delay in ms
     * @returns {Function}
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ==========================================================================
    // Empty State Handler
    // ==========================================================================

    /**
     * Setup empty state handler for DataTable
     * Call this after initializing DataTable
     */
    function setupEmptyStateHandler() {
        if (dataTable) {
            dataTable.on('draw', function() {
                var info = dataTable.page.info();
                var $tbody = $(getTableSelector() + '-tbody');
                var $emptyState = $('#emptyState');

                if (info.recordsTotal === 0) {
                    $tbody.hide();
                    $emptyState.show();
                } else {
                    $emptyState.hide();
                    $tbody.show();
                }
            });
        }
    }

    // ==========================================================================
    // CRUD Placeholder Functions - Override in child page
    // ==========================================================================

    /**
     * Create new record - override in child page
     */
    function createRecord() {
        console.warn('createRecord() not implemented. Override in child page.');
    }

    /**
     * Edit record - override in child page
     * @param {number|string} id - Record ID
     */
    function editRecord(id) {
        console.warn('editRecord() not implemented. Override in child page.');
    }

    /**
     * Delete record - override in child page
     * @param {number|string} id - Record ID
     */
    function deleteRecord(id) {
        console.warn('deleteRecord() not implemented. Override in child page.');
    }

    /**
     * Export data - override in child page
     */
    function exportData() {
        console.warn('exportData() not implemented. Override in child page.');
    }

    /**
     * Show alert dialog
     * @param {string} message - Message to display
     * @param {string} status - Alert type (success, danger, warning)
     */
    function alert_dialog(message, status) {
        swal("{{ __('common.alert') }}", message, status);
        if (status === 'success' && dataTable) {
            dataTable.draw(false);
        }
    }
</script>

{{-- Page-specific JavaScript --}}
@yield('page_js')

@endsection
