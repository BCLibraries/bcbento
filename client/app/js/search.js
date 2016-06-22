/*jslint browser:true */
/*globals $, Handlebars */

$(document).ready(function () {
    'use strict';

    var search_string, services, templates, source, loading_timers, i, max, api_version;

    api_version = '0.0.9.2';

    /**
     * Call a single search service
     * @param service the name of the service
     * @param keyword the
     */
    function callSearchService(service, keyword) {
        var $target, $heading;
        var submit_url;

        // Workaround for question mark problems.
        keyword = keyword.replace(/\?/, '');

        $target = $('#' + service.name + '-results');
        $heading = $('#' + service.name + '-results h3');
        $heading.nextAll().remove();

        loading_timers[service.name] = setTimeout(function () {
            $target.addClass('loading');
        }, 150);

        submit_url = '/search-services/v' + api_version + '/' + service.name + '?any=' + encodeURIComponent(keyword).replace('"', '%22');

        $.ajax(
            {
                type: 'GET',
                url: submit_url,
                dataType: 'jsonp',
                cache: true,
                success: function (data, status, xhr) {
                    service.postprocess(data);
                    if (data.length > service.max_results) {
                        data.splice(service.max_results, 100);
                    }
                    var html = templates[service.name](data);
                    clearTimeout(loading_timers[service.name]);
                    $target.removeClass('loading');
                    $heading.after(html);
                },
                error: function (xhr, status) {
                    clearTimeout(loading_timers[service.name]);
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
        $('#didyoumean-holder').empty();
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
            source = $('#' + services[i].name + '-template').html();
            templates[services[i].name] = Handlebars.compile(source);
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
        {
            name: 'catalog',
            max_results: 8,
            postprocess: function (data) {
                var html;
                source = $('#dym-template').html();
                html = Handlebars.compile(source)(data);
                $('#didyoumean-holder').append(html);
            }
        },
        {
            name: 'articles',
            max_results: 8,
            postprocess: emptyProcess
        },
        {
            name: 'librarians',
            max_results: 2,
            postprocess: function (data) {
                data.forEach(function (librarian) {
                        librarian.display_subjects = librarian.subjects.sort().join(', ');
                    }
                );
            }
        },
        {
            name: 'guides',
            max_results: 2,
            postprocess: emptyProcess
        }
    ];

    function emptyProcess(result) {
        return result;
    }

    templates = [];

    loading_timers = [];

    search_string = getQueryStringParam('any');

    if (!!window.history && history.pushState) {

        history.replaceState({search_string: search_string}, null, '?any=' + search_string);

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