'use strict';

// Declare app level module which depends on filters, and services
(function () {
    angular.module('myApp', [
        'ngRoute',
        'ngAnimate',
        'myApp.controllers',
        'myApp.directives',
        'myApp.filters',
        'ui.bootstrap'
    ]).
        config(function ($routeProvider, $locationProvider) {
            $locationProvider.html5Mode(true);
        });
})();
