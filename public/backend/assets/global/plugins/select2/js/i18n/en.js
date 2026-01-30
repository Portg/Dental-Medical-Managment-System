(function() {
    if (typeof jQuery.fn.select2 === 'undefined') {
        return;
    }

    jQuery.fn.select2.amd.define('select2/i18n/en', [], function () {
        // English
        return {
            errorLoading: function () {
                return 'The results could not be loaded.';
            },
            inputTooLong: function (args) {
                var overChars = args.input.length - args.maximum;

                var message = 'Please delete ' + overChars + ' character';

                if (overChars !== 1) {
                    message += 's';
                }

                return message;
            },
            inputTooShort: function (args) {
                var remainingChars = args.minimum - args.input.length;

                return 'Please enter ' + remainingChars + ' or more characters';
            },
            loadingMore: function () {
                return 'Loading more results…';
            },
            maximumSelected: function (args) {
                var message = 'You can only select ' + args.maximum + ' item';

                if (args.maximum !== 1) {
                    message += 's';
                }

                return message;
            },
            noResults: function () {
                return 'No results found';
            },
            searching: function () {
                return 'Searching…';
            },
            removeAllItems: function () {
                return 'Remove all items';
            },
            removeItem: function () {
                return 'Remove item';
            },
            search: function() {
                return 'Search';
            }
        };
    });
})();