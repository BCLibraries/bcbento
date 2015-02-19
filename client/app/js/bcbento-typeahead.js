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
                return '<div>'+result.value+' <span class="typeahead-type">' + result.type + '</span></div>';
            }
        }
    });

});