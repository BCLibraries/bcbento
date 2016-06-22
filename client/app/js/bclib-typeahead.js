$(document).ready(function () {
    engine = new Bloodhound({
        name: 'holmes-typeahead',
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

    /** Track search submissions */
    $('#block_search form').submit(function () {
        var search_type = 'all', source = '';
        search_type = $('#block_search ul.nav li.active a').attr('href').substring(1);

        if (search_type == '#articles') {
            search_type = 'pci';
        }
        if (search_type == '#books') {
            search_type = 'bc';
        }

        if (document.URL.indexOf('/search') > -1) {
            source = 'search';
        } else {
            source = 'home';
        }

        if (search_type) {
            source = source + ' ' + search_type;
        }

        $.ajax({
            'url': "http://arc.bc.edu/quick-logger/log?src=" + source + "&query=" + $('#typeahead').val(),
            'dataType': 'JSONP'
        });
        return true;
    });
});