$(document).ready(function () {

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
            $('#' + service + '-results').removeClass('loading');
            $('#' + service + '-results').append(html);
        });
    }

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }
});