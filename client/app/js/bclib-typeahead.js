/*jslint browser:true */
/*globals $, Handlebars, Bloodhound */

$(document).ready(function () {
    'use strict';

    var engine, api_version;

    api_version = '0.0.7';

    engine = new Bloodhound({
        name: 'holmes-typeahead',
        remote: {
            url: '/search-services/v' + api_version + '/typeahead?any=%QUERY&callback=?',
            rateLimitWait: 100,
            rateLimitBy: 'throttle'
        },
        datumTokenizer: function (d) {
            return Bloodhound.tokenizers.whitespace(d.val);
        },
        queryTokenizer: Bloodhound.tokenizers.whitespace
    });
    engine.initialize();
    $('#typeahead').typeahead({
        hint: false,
        minLength: 3
    }, {
        source: engine.ttAdapter(),
        templates: {
            empty: '',
            suggestion: function (result) {
                var display = truncate(result.value, 80);
                result.value = result.value.replace(/…$/, '');
                return '<div><span class="summary">' + display + '</span></div>';
            },
            header: '<h3>Search suggestions</h3>'
        }
    });

    function truncate(str, length) {
        var too_long, s_;
        too_long = str.length > length;
        s_ = too_long ? str.substr(0, length - 1) : str;
        s_ = too_long ? s_.substr(0, s_.lastIndexOf(' ')) : s_;
        return too_long ? s_ + '&hellip;' : s_;
    }
});