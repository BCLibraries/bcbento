'use strict';

// Declare app level module which depends on filters, and services
(function () {
    angular.module('myApp', [
        'ngRoute',
        'myApp.controllers',
        'ui.bootstrap'
    ]).
        config(['$routeProvider', function ($routeProvider) {
            $routeProvider.when('/bento', {templateUrl: 'partials/bento.html', controller: 'bento'});
            $routeProvider.otherwise({templateUrl: 'partials/typeahead', controller: 'bento'});
        }]);
})();
