/*jslint browser:true */
/*globals $, Handlebars */

$(document).ready(function () {
    'use strict';

    var search_string, services, templates, source, loading_timers, i, max;

    /**
     * Call a single search service
     * @param service the name of the service
     * @param keyword the
     */
    function callSearchService(service, keyword) {
        var $target, $heading;
        $target = $('#' + service + '-results');
        $heading = $('#' + service + '-results h3');
        $heading.nextAll().remove();

        loading_timers[service] = setTimeout(function () {
            $target.addClass('loading');
        }, 150);

        $.ajax(
            {
                type: 'GET',
                url: '/search-services/' + service + '?any=' + keyword,
                dataType: 'jsonp',
                cache: true,
                success: function (data, status, xhr) {
                    var html = templates[service](data);
                    clearTimeout(loading_timers[service]);
                    $target.removeClass('loading');
                    $heading.after(html);
                },
                error: function (xhr, status) {
                    clearTimeout(loading_timers[service]);
                    $target.removeClass('loading');
                }
            }
        );
    }

    /**
     * Search all services
     * @param keyword
     */
    function search(keyword) {
        var $typeahead = $('#typeahead');
        setTitle(keyword);
        $typeahead.typeahead('close');
        for (i = 0, max = services.length; i < max; i += 1) {
            callSearchService(services[i], keyword);
        }
        $typeahead.typeahead('val', keyword.replace(/\+/g, ' '));
    }

    /**
     * Set page title
     * @param keyword
     */
    function setTitle(keyword) {
        var display_keyword = keyword.replace(/\+/g, ' ');
        if (keyword) {
            document.title = 'Search BC Libraries for "' + truncate(display_keyword, 40) + '"';
        }
    }

    /**
     * Get a parameter from the query string
     * @param name
     * @returns {string}
     */
    function getQueryStringParam(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    /**
     * Render the search results
     * @param services
     */
    function renderSearchResults(services) {
        for (i = 0, max = services.length; i < max; i += 1) {
            source = $('#' + services[i] + '-template').html();
            templates[services[i]] = Handlebars.compile(source);
        }
    }


    /**
     * Truncate string and add ellipses
     * @param str
     * @param length
     * @returns string
     */
    function truncate(str, length) {
        var too_long, s_;
        too_long = str.length > length;
        s_ = too_long ? str.substr(0, length - 1) : str;
        s_ = too_long ? s_.substr(0, s_.lastIndexOf(' ')) : s_;
        return too_long ? s_ + '…' : s_;
    }

    services = [
        'catalog',
        'articles',
        'librarians',
        'guides'
    ];

    templates = [];

    loading_timers = [];

    search_string = getQueryStringParam('any');

    if (!!window.history && history.pushState) {

        history.replaceState({search_string: search_string}, document.title);

        window.onpopstate = function (event) {
            search(event.state.search_string);
        };

        $('#bcbento-search').submit(function () {
            var new_search = $('#typeahead').val();
            new_search = new_search.replace(/\s/g, '+');
            history.pushState({search_string: new_search}, null, '?any=' + new_search);
            search(new_search);
            return false;
        });
    }

    Handlebars.registerHelper('truncate', function (max_length, text) {
        var too_long, string;
        too_long = text.length > max_length;
        if (too_long) {
            string = text.substr(0, max_length - 1);
            string = string.substr(0, string.lastIndexOf(' ')) + '…';
        } else {
            string = text;
        }
        return string;
    });

    renderSearchResults(services);
    search(search_string);
    $('#typeahead').val(search_string);

});