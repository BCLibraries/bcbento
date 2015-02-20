$(document).ready(function () {

    var search_string, engine, services, templates, source, loading_timers;

    services = [
        'catalog',
        'articles',
        'librarians',
        'guides'
    ];

    templates = [];

    loading_timers = [];

    search_string = getParameterByName('any');

    if (!!window.history && history.pushState) {

        history.replaceState({search_string: search_string});

        window.onpopstate = function (event) {
            search(event.state.search_string);
        };

        $('#bcbento-search').submit(function () {
            var search_string = $('#typeahead').val();
            search_string = search_string.replace(/\s/g, '+');
            history.pushState({search_string: search_string}, null, '?any=' + search_string);
            search(search_string);
            return false;
        });
    }

    Handlebars.registerHelper('truncate', function (max_length, text) {
        var too_long, s_;
        too_long = text.length > max_length;
        if (too_long) {
            s_ = text.substr(0, max_length - 1);
            s_ = s_.substr(0, s_.lastIndexOf(' ')) + '…';
        } else {
            s_ = text;
        }
        return s_;
    });

    for (var i = 0; i < services.length; i++) {
        source = $('#' + services[i] + '-template').html();
        templates[services[i]] = Handlebars.compile(source);
    }

    search(search_string);

    function search(keyword) {
        $('#typeahead').typeahead('close');
        for (var i = 0; i < services.length; i++) {
            callSearchService(services[i], keyword);
        }
        $('#typeahead').typeahead('val', keyword.replace(/\+/g, ' '));
    }

    function callSearchService(service, keyword) {
        var $target, $heading;
        $target = $('#' + service + '-results');
        $heading = $('#' + service + '-results h3');
        $heading.nextAll().remove();

        loading_timers[service] = setTimeout(function () {
            $target.addClass('loading');
        }, 150);

        $.ajax({
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

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

});