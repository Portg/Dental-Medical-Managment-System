/**
 * Template Picker - Quick template insertion for SOAP and medical notes
 * Triggers on "/" key in enabled textareas
 */

var TemplatePicker = (function () {
    var baseUrl = '';
    var currentInput = null;
    var currentTemplates = [];
    var selectedIndex = 0;
    var isVisible = false;
    var searchTimeout = null;
    var onInsertCallback = null;

    // CSS styles for the picker dropdown
    var pickerStyles = `
        .template-picker-dropdown {
            position: absolute;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            max-height: 300px;
            overflow-y: auto;
            z-index: 10000;
            min-width: 300px;
            max-width: 500px;
        }
        .template-picker-header {
            padding: 8px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .template-picker-header input {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 4px 8px;
            font-size: 13px;
        }
        .template-picker-close {
            margin-left: 8px;
            cursor: pointer;
            color: #999;
        }
        .template-picker-close:hover {
            color: #333;
        }
        .template-picker-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .template-picker-item:hover,
        .template-picker-item.selected {
            background: #f0f7ff;
        }
        .template-picker-item-name {
            font-weight: 600;
            color: #333;
        }
        .template-picker-item-code {
            font-size: 12px;
            color: #999;
            margin-left: 8px;
        }
        .template-picker-item-desc {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        .template-picker-item-category {
            display: inline-block;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 8px;
        }
        .template-picker-item-category.system {
            background: #e3f2fd;
            color: #1976d2;
        }
        .template-picker-item-category.department {
            background: #e8f5e9;
            color: #388e3c;
        }
        .template-picker-item-category.personal {
            background: #fff3e0;
            color: #f57c00;
        }
        .template-picker-empty {
            padding: 20px;
            text-align: center;
            color: #999;
        }
        .template-picker-loading {
            padding: 20px;
            text-align: center;
            color: #666;
        }
    `;

    function init(options) {
        baseUrl = options.baseUrl || '';
        onInsertCallback = options.onInsert || null;

        // Inject styles
        if (!document.getElementById('template-picker-styles')) {
            var style = document.createElement('style');
            style.id = 'template-picker-styles';
            style.textContent = pickerStyles;
            document.head.appendChild(style);
        }

        // Listen for keyup on template-enabled textareas
        $(document).on('keyup', '.template-enabled', function (e) {
            handleKeyUp(e, $(this));
        });

        // Listen for keydown for navigation
        $(document).on('keydown', '.template-enabled', function (e) {
            if (isVisible) {
                handleKeyDown(e);
            }
        });

        // Close picker when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.template-picker-dropdown').length &&
                !$(e.target).hasClass('template-enabled')) {
                hidePicker();
            }
        });
    }

    function handleKeyUp(e, $input) {
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Check if "/" was just typed
        if (e.key === '/' && cursorPos > 0) {
            // Check if this is a standalone "/" (beginning of line or after space)
            var charBefore = cursorPos > 1 ? val.charAt(cursorPos - 2) : '';
            if (cursorPos === 1 || charBefore === '' || charBefore === '\n' || charBefore === ' ') {
                currentInput = $input;
                showPicker($input);
                return;
            }
        }

        // Update search if picker is visible
        if (isVisible && currentInput && currentInput[0] === $input[0]) {
            var searchText = getSearchText($input);
            if (searchText !== null) {
                searchTemplates(searchText);
            } else {
                hidePicker();
            }
        }
    }

    function handleKeyDown(e) {
        if (!isVisible) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectNext();
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectPrev();
                break;
            case 'Enter':
                e.preventDefault();
                selectCurrent();
                break;
            case 'Escape':
                e.preventDefault();
                hidePicker();
                break;
            case 'Tab':
                e.preventDefault();
                selectCurrent();
                break;
        }
    }

    function getSearchText($input) {
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Find the "/" before cursor
        var searchStart = -1;
        for (var i = cursorPos - 1; i >= 0; i--) {
            if (val.charAt(i) === '/') {
                // Check if this is a valid trigger position
                var charBefore = i > 0 ? val.charAt(i - 1) : '';
                if (i === 0 || charBefore === '\n' || charBefore === ' ') {
                    searchStart = i + 1;
                    break;
                }
            }
            // Stop if we hit a newline or too far back
            if (val.charAt(i) === '\n' || cursorPos - i > 30) {
                break;
            }
        }

        if (searchStart === -1) return null;
        return val.substring(searchStart, cursorPos);
    }

    function showPicker($input) {
        var type = $input.data('template-type') || 'progress_note';
        isVisible = true;
        selectedIndex = 0;

        // Close PhrasePicker if open (mutual exclusion)
        if (typeof PhrasePicker !== 'undefined') {
            PhrasePicker.hide();
        }

        // Create dropdown if not exists
        if (!$('.template-picker-dropdown').length) {
            $('body').append('<div class="template-picker-dropdown"></div>');
        }

        var $picker = $('.template-picker-dropdown');

        // Position the dropdown
        var offset = $input.offset();
        var inputHeight = $input.outerHeight();
        $picker.css({
            top: offset.top + inputHeight + 5,
            left: offset.left
        });

        // Show loading
        $picker.html('<div class="template-picker-loading"><i class="fa fa-spinner fa-spin"></i> ' +
            (LanguageManager.trans('common.loading') || 'Loading...') + '</div>');
        $picker.show();

        // Load templates
        loadTemplates(type, '');
    }

    function hidePicker() {
        isVisible = false;
        currentInput = null;
        currentTemplates = [];
        selectedIndex = 0;
        $('.template-picker-dropdown').hide();
    }

    function loadTemplates(type, query) {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(function () {
            $.ajax({
                url: baseUrl + '/medical-templates-search',
                type: 'GET',
                data: {type: type, q: query},
                success: function (response) {
                    if (response.status && response.data) {
                        currentTemplates = response.data;
                        renderTemplates();
                    }
                },
                error: function () {
                    renderEmpty();
                }
            });
        }, 200);
    }

    function searchTemplates(query) {
        if (!currentInput) return;
        var type = currentInput.data('template-type') || 'progress_note';
        loadTemplates(type, query);
    }

    function renderTemplates() {
        var $picker = $('.template-picker-dropdown');

        if (currentTemplates.length === 0) {
            renderEmpty();
            return;
        }

        var html = '<div class="template-picker-header">' +
            '<input type="text" class="template-search-input" placeholder="' +
            (LanguageManager.trans('templates.search_templates') || 'Search templates...') + '">' +
            '<span class="template-picker-close">&times;</span>' +
            '</div>';

        html += '<div class="template-picker-list">';
        currentTemplates.forEach(function (template, index) {
            var selectedClass = index === selectedIndex ? ' selected' : '';
            html += '<div class="template-picker-item' + selectedClass + '" data-index="' + index + '">' +
                '<div>' +
                '<span class="template-picker-item-name">' + template.name + '</span>' +
                '<span class="template-picker-item-code">/' + template.code + '</span>' +
                '<span class="template-picker-item-category ' + template.category + '">' +
                template.category + '</span>' +
                '</div>';
            if (template.description) {
                html += '<div class="template-picker-item-desc">' + template.description + '</div>';
            }
            html += '</div>';
        });
        html += '</div>';

        $picker.html(html);

        // Bind events
        $picker.find('.template-picker-item').on('click', function () {
            selectedIndex = parseInt($(this).data('index'));
            selectCurrent();
        });

        $picker.find('.template-picker-close').on('click', function () {
            hidePicker();
        });

        $picker.find('.template-search-input').on('input', function () {
            var query = $(this).val();
            searchTemplates(query);
        });
    }

    function renderEmpty() {
        var $picker = $('.template-picker-dropdown');
        $picker.html('<div class="template-picker-empty">' +
            (LanguageManager.trans('templates.no_templates_found') || 'No templates found') + '</div>');
    }

    function selectNext() {
        if (selectedIndex < currentTemplates.length - 1) {
            selectedIndex++;
            updateSelection();
        }
    }

    function selectPrev() {
        if (selectedIndex > 0) {
            selectedIndex--;
            updateSelection();
        }
    }

    function updateSelection() {
        var $items = $('.template-picker-item');
        $items.removeClass('selected');
        $items.eq(selectedIndex).addClass('selected');

        // Scroll into view
        var $selected = $items.eq(selectedIndex);
        var $list = $('.template-picker-dropdown');
        if ($selected.length && $list.length) {
            var itemTop = $selected.position().top;
            var itemBottom = itemTop + $selected.outerHeight();
            var listHeight = $list.height();

            if (itemBottom > listHeight) {
                $list.scrollTop($list.scrollTop() + itemBottom - listHeight + 10);
            } else if (itemTop < 0) {
                $list.scrollTop($list.scrollTop() + itemTop - 10);
            }
        }
    }

    function selectCurrent() {
        if (currentTemplates.length === 0 || selectedIndex >= currentTemplates.length) {
            hidePicker();
            return;
        }

        var template = currentTemplates[selectedIndex];
        insertTemplate(template);

        // Increment usage count
        $.post(baseUrl + '/medical-templates/' + template.id + '/increment-usage');

        hidePicker();
    }

    function insertTemplate(template) {
        if (!currentInput) return;

        var $input = currentInput;
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Find the "/" trigger position
        var slashPos = -1;
        for (var i = cursorPos - 1; i >= 0; i--) {
            if (val.charAt(i) === '/') {
                var charBefore = i > 0 ? val.charAt(i - 1) : '';
                if (i === 0 || charBefore === '\n' || charBefore === ' ') {
                    slashPos = i;
                    break;
                }
            }
            if (val.charAt(i) === '\n') break;
        }

        // Remove the slash command from the input first
        if (slashPos !== -1) {
            var before = val.substring(0, slashPos);
            var after = val.substring(cursorPos);
            $input.val(before + after);
        }

        // Call custom insert handler if provided
        if (onInsertCallback && typeof onInsertCallback === 'function') {
            var handled = onInsertCallback(template, $input);
            if (handled) {
                // Custom handler took care of insertion
                return;
            }
        }

        // Default behavior: insert template content into current field
        var content = template.content;
        try {
            var parsed = JSON.parse(content);
            if (typeof parsed === 'object') {
                // Format JSON content nicely
                content = formatTemplateContent(parsed);
            }
        } catch (e) {
            // Content is plain text, use as-is
        }

        // Insert content at cursor position
        val = $input.val();
        cursorPos = $input[0].selectionStart;
        var newVal = val.substring(0, cursorPos) + content + val.substring(cursorPos);

        $input.val(newVal);

        // Set cursor position after inserted content
        var newCursorPos = cursorPos + content.length;
        $input[0].setSelectionRange(newCursorPos, newCursorPos);
        $input.focus();
    }

    function formatTemplateContent(obj) {
        var lines = [];
        if (obj.subjective) lines.push('S: ' + obj.subjective);
        if (obj.objective) lines.push('O: ' + obj.objective);
        if (obj.assessment) lines.push('A: ' + obj.assessment);
        if (obj.plan) lines.push('P: ' + obj.plan);
        if (obj.content) lines.push(obj.content);
        return lines.join('\n');
    }

    return {
        init: init,
        show: showPicker,
        hide: hidePicker
    };
})();

/**
 * Quick Phrase Picker - For inserting common medical phrases
 * Triggered by input matching phrase shortcuts (exact shortcut match)
 */
var QuickPhrasePicker = (function () {
    var baseUrl = '';
    var phraseCache = {};

    function init(options) {
        baseUrl = options.baseUrl || '';

        // Load all phrases on init
        loadPhrases();

        // Listen for phrase shortcuts
        $(document).on('input', '.phrase-enabled', function () {
            checkForPhrase($(this));
        });
    }

    function loadPhrases() {
        $.ajax({
            url: baseUrl + '/quick-phrases-search',
            type: 'GET',
            success: function (response) {
                if (response.status && response.data) {
                    response.data.forEach(function (phrase) {
                        phraseCache[phrase.shortcut.toLowerCase()] = phrase.phrase;
                    });
                }
            }
        });
    }

    function checkForPhrase($input) {
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Get the word before cursor
        var wordStart = cursorPos;
        while (wordStart > 0 && val.charAt(wordStart - 1) !== ' ' && val.charAt(wordStart - 1) !== '\n') {
            wordStart--;
        }

        var word = val.substring(wordStart, cursorPos).toLowerCase();

        if (phraseCache[word]) {
            // Replace the shortcut with the full phrase
            var before = val.substring(0, wordStart);
            var after = val.substring(cursorPos);
            var phrase = phraseCache[word];
            var newVal = before + phrase + after;

            $input.val(newVal);

            var newCursorPos = before.length + phrase.length;
            $input[0].setSelectionRange(newCursorPos, newCursorPos);
        }
    }

    return {
        init: init,
        reload: loadPhrases
    };
})();

/**
 * PhrasePicker - Semicolon-triggered phrase selector dropdown
 * Triggers on ";" key in phrase-enabled textareas, searches via /quick-phrases-search?q=xxx
 * Mutually exclusive with TemplatePicker (one closes the other)
 */
var PhrasePicker = (function () {
    var baseUrl = '';
    var currentInput = null;
    var currentPhrases = [];
    var selectedIndex = 0;
    var isVisible = false;
    var searchTimeout = null;

    // CSS styles for the phrase picker dropdown
    var pickerStyles = '\
        .phrase-picker-dropdown {\
            position: absolute;\
            background: #fff;\
            border: 1px solid #ddd;\
            border-radius: 4px;\
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);\
            max-height: 300px;\
            overflow-y: auto;\
            z-index: 10000;\
            min-width: 280px;\
            max-width: 450px;\
        }\
        .phrase-picker-header {\
            padding: 8px 12px;\
            background: #f0f7ff;\
            border-bottom: 1px solid #ddd;\
            display: flex;\
            justify-content: space-between;\
            align-items: center;\
            font-size: 12px;\
            color: #666;\
        }\
        .phrase-picker-close {\
            margin-left: 8px;\
            cursor: pointer;\
            color: #999;\
        }\
        .phrase-picker-close:hover {\
            color: #333;\
        }\
        .phrase-picker-item {\
            padding: 8px 12px;\
            cursor: pointer;\
            border-bottom: 1px solid #eee;\
        }\
        .phrase-picker-item:hover,\
        .phrase-picker-item.selected {\
            background: #f0f7ff;\
        }\
        .phrase-picker-item-shortcut {\
            font-weight: 600;\
            color: #1976d2;\
            font-size: 12px;\
            margin-right: 8px;\
        }\
        .phrase-picker-item-text {\
            color: #333;\
        }\
        .phrase-picker-item-category {\
            display: inline-block;\
            font-size: 10px;\
            padding: 1px 5px;\
            border-radius: 3px;\
            margin-left: 6px;\
            background: #e8f5e9;\
            color: #388e3c;\
        }\
        .phrase-picker-empty {\
            padding: 20px;\
            text-align: center;\
            color: #999;\
        }\
        .phrase-picker-loading {\
            padding: 20px;\
            text-align: center;\
            color: #666;\
        }\
    ';

    function init(options) {
        baseUrl = options.baseUrl || '';

        // Inject styles
        if (!document.getElementById('phrase-picker-styles')) {
            var style = document.createElement('style');
            style.id = 'phrase-picker-styles';
            style.textContent = pickerStyles;
            document.head.appendChild(style);
        }

        // Listen for keyup on phrase-enabled textareas
        $(document).on('keyup', '.phrase-enabled', function (e) {
            handleKeyUp(e, $(this));
        });

        // Listen for keydown for navigation
        $(document).on('keydown', '.phrase-enabled', function (e) {
            if (isVisible) {
                handleKeyDown(e);
            }
        });

        // Close picker when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.phrase-picker-dropdown').length &&
                !$(e.target).hasClass('phrase-enabled')) {
                hidePicker();
            }
        });
    }

    function handleKeyUp(e, $input) {
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Check if ";" was just typed
        if (e.key === ';' && cursorPos > 0) {
            var charBefore = cursorPos > 1 ? val.charAt(cursorPos - 2) : '';
            if (cursorPos === 1 || charBefore === '' || charBefore === '\n' || charBefore === ' ') {
                currentInput = $input;
                // Close TemplatePicker if open
                if (typeof TemplatePicker !== 'undefined') {
                    TemplatePicker.hide();
                }
                showPicker($input);
                return;
            }
        }

        // Update search if picker is visible
        if (isVisible && currentInput && currentInput[0] === $input[0]) {
            var searchText = getSearchText($input);
            if (searchText !== null) {
                searchPhrases(searchText);
            } else {
                hidePicker();
            }
        }
    }

    function handleKeyDown(e) {
        if (!isVisible) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectNext();
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectPrev();
                break;
            case 'Enter':
                e.preventDefault();
                selectCurrent();
                break;
            case 'Escape':
                e.preventDefault();
                hidePicker();
                break;
            case 'Tab':
                e.preventDefault();
                selectCurrent();
                break;
        }
    }

    function getSearchText($input) {
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Find the ";" before cursor
        var searchStart = -1;
        for (var i = cursorPos - 1; i >= 0; i--) {
            if (val.charAt(i) === ';') {
                var charBefore = i > 0 ? val.charAt(i - 1) : '';
                if (i === 0 || charBefore === '\n' || charBefore === ' ') {
                    searchStart = i + 1;
                    break;
                }
            }
            // Stop if we hit a newline or too far back
            if (val.charAt(i) === '\n' || cursorPos - i > 30) {
                break;
            }
        }

        if (searchStart === -1) return null;
        return val.substring(searchStart, cursorPos);
    }

    function showPicker($input) {
        isVisible = true;
        selectedIndex = 0;

        // Create dropdown if not exists
        if (!$('.phrase-picker-dropdown').length) {
            $('body').append('<div class="phrase-picker-dropdown"></div>');
        }

        var $picker = $('.phrase-picker-dropdown');

        // Position the dropdown
        var offset = $input.offset();
        var inputHeight = $input.outerHeight();
        $picker.css({
            top: offset.top + inputHeight + 5,
            left: offset.left
        });

        // Show loading
        $picker.html('<div class="phrase-picker-loading"><i class="fa fa-spinner fa-spin"></i> ' +
            (LanguageManager.trans('common.loading') || 'Loading...') + '</div>');
        $picker.show();

        // Load phrases
        loadPhrases('');
    }

    function hidePicker() {
        isVisible = false;
        currentInput = null;
        currentPhrases = [];
        selectedIndex = 0;
        $('.phrase-picker-dropdown').hide();
    }

    function loadPhrases(query) {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(function () {
            $.ajax({
                url: baseUrl + '/quick-phrases-search',
                type: 'GET',
                data: { q: query },
                success: function (response) {
                    if (response.status && response.data) {
                        currentPhrases = response.data;
                        renderPhrases();
                    }
                },
                error: function () {
                    renderEmpty();
                }
            });
        }, 200);
    }

    function searchPhrases(query) {
        if (!currentInput) return;
        loadPhrases(query);
    }

    function renderPhrases() {
        var $picker = $('.phrase-picker-dropdown');

        if (currentPhrases.length === 0) {
            renderEmpty();
            return;
        }

        var headerText = LanguageManager.trans('common.quick_phrases') || 'Quick Phrases';
        var html = '<div class="phrase-picker-header">' +
            '<span>' + headerText + ' (' + currentPhrases.length + ')</span>' +
            '<span class="phrase-picker-close">&times;</span>' +
            '</div>';

        html += '<div class="phrase-picker-list">';
        currentPhrases.forEach(function (phrase, index) {
            var selectedClass = index === selectedIndex ? ' selected' : '';
            html += '<div class="phrase-picker-item' + selectedClass + '" data-index="' + index + '">' +
                '<span class="phrase-picker-item-shortcut">' + phrase.shortcut + '</span>' +
                '<span class="phrase-picker-item-text">' + phrase.phrase + '</span>';
            if (phrase.category_name) {
                html += '<span class="phrase-picker-item-category">' + phrase.category_name + '</span>';
            }
            html += '</div>';
        });
        html += '</div>';

        $picker.html(html);

        // Bind events
        $picker.find('.phrase-picker-item').on('click', function () {
            selectedIndex = parseInt($(this).data('index'));
            selectCurrent();
        });

        $picker.find('.phrase-picker-close').on('click', function () {
            hidePicker();
        });
    }

    function renderEmpty() {
        var $picker = $('.phrase-picker-dropdown');
        var emptyText = LanguageManager.trans('common.no_matching_phrases') || 'No matching phrases found';
        $picker.html('<div class="phrase-picker-empty">' + emptyText + '</div>');
    }

    function selectNext() {
        if (selectedIndex < currentPhrases.length - 1) {
            selectedIndex++;
            updateSelection();
        }
    }

    function selectPrev() {
        if (selectedIndex > 0) {
            selectedIndex--;
            updateSelection();
        }
    }

    function updateSelection() {
        var $items = $('.phrase-picker-item');
        $items.removeClass('selected');
        $items.eq(selectedIndex).addClass('selected');

        // Scroll into view
        var $selected = $items.eq(selectedIndex);
        var $list = $('.phrase-picker-dropdown');
        if ($selected.length && $list.length) {
            var itemTop = $selected.position().top;
            var itemBottom = itemTop + $selected.outerHeight();
            var listHeight = $list.height();

            if (itemBottom > listHeight) {
                $list.scrollTop($list.scrollTop() + itemBottom - listHeight + 10);
            } else if (itemTop < 0) {
                $list.scrollTop($list.scrollTop() + itemTop - 10);
            }
        }
    }

    function selectCurrent() {
        if (currentPhrases.length === 0 || selectedIndex >= currentPhrases.length) {
            hidePicker();
            return;
        }

        var phrase = currentPhrases[selectedIndex];
        insertPhrase(phrase);
        hidePicker();
    }

    function insertPhrase(phrase) {
        if (!currentInput) return;

        var $input = currentInput;
        var val = $input.val();
        var cursorPos = $input[0].selectionStart;

        // Find the ";" trigger position
        var semicolonPos = -1;
        for (var i = cursorPos - 1; i >= 0; i--) {
            if (val.charAt(i) === ';') {
                var charBefore = i > 0 ? val.charAt(i - 1) : '';
                if (i === 0 || charBefore === '\n' || charBefore === ' ') {
                    semicolonPos = i;
                    break;
                }
            }
            if (val.charAt(i) === '\n') break;
        }

        if (semicolonPos === -1) return;

        // Remove ";searchtext" and insert the phrase text
        var before = val.substring(0, semicolonPos);
        var after = val.substring(cursorPos);
        var newVal = before + phrase.phrase + after;

        $input.val(newVal);

        // Set cursor position after inserted phrase
        var newCursorPos = before.length + phrase.phrase.length;
        $input[0].setSelectionRange(newCursorPos, newCursorPos);
        $input.focus();
    }

    return {
        init: init,
        show: showPicker,
        hide: hidePicker
    };
})();
