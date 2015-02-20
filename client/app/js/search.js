$(document).ready(function () {

    var search_string = getParameterByName('any');

    if (!!window.history && history.pushState) {

        history.replaceState({search_string: search_string});

        window.onpopstate = function (event) {
            search(event.state.search_string);
        };

        $('#bcbento-search').submit(function () {
            var search_string = $('#typeahead').val();
            var state = {search_string: search_string};
            search_string = search_string.replace(/\s/g, '+');
            history.pushState(state, null, '?any=' + search_string);
            search(search_string);
            return false;
        });
    }

    search(search_string);

    var engine = new Bloodhound({
        name: 'animals',
        local: [{val: 'dog'}, {val: 'pig'}, {val: 'moose'}],
        remote: {
            url: '/search-services/typeahead?any=%QUERY&callback=?',
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
                return '<div>' + result.value + ' <span class="typeahead-type">' + result.type + '</span></div>';
            }
        }
    });

    function search(keyword) {
        var services = [
            'catalog',
            'articles',
            'librarians',
            'guides'
        ];

        for (var i = 0; i < services.length; i++) {
            callService(services[i], keyword);
        }

        $('#typeahead').typeahead('val', keyword.replace(/\+/g, ' '));
    }

    function callService(service, keyword) {
        $('#' + service + '-results').empty().addClass('loading');

        var url = '/search-services/' + service + '?any=' + keyword;
        $.ajax({
                type: 'GET',
                url: url,
                dataType: 'jsonp',
                cache: true,
                success: function (data, status, xhr) {
                    var source = $('#' + service + '-template').html();
                    var html = Mustache.to_html(source, data);
                    $('#' + service + '-results').removeClass('loading').append(html);
                },
                error: function (xhr, status) {
                    $('#' + service + '-results').removeClass('loading');
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