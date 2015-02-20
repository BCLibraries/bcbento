$(document).ready(function () {

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

    $('#typeahead').typeahead('val', getParameterByName('any'))

    var services = [
        'catalog',
        'articles',
        'librarians',
        'guides'
    ];

    for (var i = 0; i < services.length; i++) {
        callService(services[i]);
    }

    function callService(service) {
        $('#' + service + '-results').addClass('loading');

        var url = '/search-services/' + service + '?any=' + getParameterByName('any') + '&callback=?';
        $.getJSON(url, function (data, status, xhr) {
            var source = $('#' + service + '-template').html();
            var html = Mustache.to_html(source, data);
            $('#' + service + '-results').removeClass('loading').append(html);
        }).fail(function (xhr, status) {
            $('#' + service + '-results').removeClass('loading');
        });
    }

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

});