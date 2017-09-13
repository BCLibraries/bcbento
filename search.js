/*jslint browser:true */
/*globals $, Handlebars */

'use strict';

/**
* Function that tracks a click on an outbound link in Analytics.
* This function takes a valid URL string as an argument, and uses that URL string
* as the event label. Setting the transport method to 'beacon' lets the hit be sent
* using 'navigator.sendBeacon' in browser that support it.
*/
var trackOutboundLink = function(url) {
    if (ga.q) {
        // Google Analytics is blocked
        // http://veithen.github.io/2015/01/24/outbound-link-tracking.html
        document.location = url;
    } else {
        ga('send', 'event', 'outbound', 'click', url, {
            'transport': 'beacon',
            'hitCallback': function(){document.location = url;}
        });
    }
}

$.fn.bcBento = function (services, service_url_base) {

    var search_string, templates, source, loading_timers, i, max, api_version;

    api_version = '0.0.9.2';

    function callSearchService(service, keyword) {
        var $target, $heading, url;

        $target = $('#' + service.name + '-results');
        $heading = $('#' + service.name + '-results h3');

        // Workaround for question mark and double-quote problems.
        keyword = keyword.replace(/\?/, '');

        url = '/search-services/' + service.name + '?any=' + encodeURIComponent(keyword);
        url = url.replace(/%2B/,'+').replace('"','%22');

        // Clear old results.
        $heading.nextAll().remove();
        loading_timers[service.name] = setTimeout(function () {
            $target.addClass('loading');
        }, 150);


        $.ajax(
            {
                type: 'GET',
                url: url,
                dataType: 'jsonp',
                cache: true,
                success: function (data, status, xhr) {
                    successfulSearch(data, status, xhr, service, $target, $heading);
                },
                error: function (xhr, status) {
                    clearTimeout(loading_timers[service.name]);
                    $target.removeClass('loading');
                }
            }
        );
    }

    function successfulSearch(data, status, xhr, service, $target, $heading) {
        if (typeof service.postprocess != 'undefined') {
            service.postprocess(data);
        }

        if (data.items && data.items.length > service.max_results) {
            data.items = data.items.slice(0, service.max_results);
        }

        if (templates[service.name]) {
            var html = templates[service.name](data);
            clearTimeout(loading_timers[service.name]);
            $target.removeClass('loading');
            $heading.after(html);
        }
    }

    function search(keyword) {
        var $typeahead = $('#typeahead');
        $('#didyoumean-holder').empty();
        setTitle(keyword);
        $typeahead.typeahead('close');
        services.forEach(function (service) {
            callSearchService(service, keyword);
        });
        $typeahead.typeahead('val', keyword.replace(/\+/g, ' '));
    }

    function setTitle(keyword) {
        var display_keyword = keyword.replace(/\+/g, ' ');
        if (keyword) {
            document.title = 'Search BC Libraries for "' + truncate(40, display_keyword) + '"';
        }
    }

    function getQueryStringParam(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    function renderServiceResults(service) {
        source = $('#' + service.name + '-template').html();
        if (source) {
            templates[service.name] = Handlebars.compile(source);
        }
    }

    function truncate(max_length, str) {
        if (str.length > max_length) {
            str = str.substr(0, max_length - 1);
            str = str.substr(0, str.lastIndexOf(' ')) + '…';
        }
        return str;
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

    Handlebars.registerHelper('truncate', truncate);

    services.forEach(renderServiceResults);
    search(search_string);
    $('#typeahead').val(search_string);
};


$(document).ready(function () {

    // Define services
    var catalog = {
        name: 'catalog',
        max_results: 8,
        postprocess: function (data) {
            var html, source;
            source = $('#dym-template').html();
            html = Handlebars.compile(source)(data);
            $('#didyoumean-holder').append(html);
        }
    }

    var articles = {
        name: 'articles',
        max_results: 8,
    };

    var librarians = {
        name: 'librarians',
        max_results: 2,
        postprocess: function (data) {
            data.forEach(function (librarian) {
                    librarian.display_subjects = librarian.subjects.sort().join(', ');
                }
            );
        }
    };

    var springshare = {
        name: 'springshare',
        max_results: 5
    }

    var service_url_base = ''

    $(document).bcBento([catalog, articles, librarians, springshare], service_url_base);
});