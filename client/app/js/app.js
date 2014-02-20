'use strict';

// Declare app level module which depends on filters, and services
angular.module('myApp', [
  'ngRoute',
  'myApp.filters',
  'myApp.services',
  'myApp.directives',
  'myApp.controllers',
        'ui.bootstrap'
]).
config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/bento', {templateUrl: 'partials/bento.html', controller: 'bento'});
  $routeProvider.when('/catalog', {templateUrl: 'partials/catalog.html', controller: 'catalog'});
  $routeProvider.when('/databases', {templateUrl: 'partials/databases.html', controller: 'databases'});
  $routeProvider.when('/articles', {templateUrl: 'partials/articles.html', controller: 'articles'});
  $routeProvider.otherwise({redirectTo: '/bento'});
}]);