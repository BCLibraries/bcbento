'use strict';

/* Controllers */

angular.module('myApp.controllers', []).
    controller('MyCtrl1', ['$http', '$scope', '$location',

        function ($http, $scope, $location) {

            var base_url, search, search_services;

            // Add new services here.
            search_services = ['catalog', 'articles', 'dpla'];

            if ($location.search().any) {

                base_url = '/search-services/';
                search = '?any=' + $location.search().any;

                for (var i = 0; i < search_services.length; i++) {
                    fetch(search_services[i]);
                }
            } else {
                for (var i = 0; i < search_services.length; i++) {
                    $scope[search_services[i] + '_results'] = [];
                }
            }

            function fetch(search_service) {
                $http.get(base_url + search_service + search, {'cache': true}).success(
                    function (data) {
                        console.log(search_service);
                        console.log(data);
                        $scope[search_service + '_results'] = data;
                    }
                ).error(
                    function (data, status) {
                        console.log(status);
                    }
                );
            }
        }])
    .controller('AutoComplete', [ '$scope', '$http',
        function ($scope, $http) {
            $scope.selected = undefined;

            $scope.getLocation = function (val) {
                return $http.get('http://localhost/search-services/suggest?text=' + val).then(function (res) {
                    var suggestions = [];
                    console.log(res.data.ac[0]);
                    angular.forEach(res.data.ac[0].options, function (item) {
                        suggestions.push(item.text);
                    });
                    return suggestions;
                });
            };

        }]);

String.prototype.truncate = function(max_length) {
    var too_long, s_;
    too_long = this.length > max_length;
    if (too_long) {
        s_ = this.substr(0, max_length - 1);
        s_ = s_.substr(0, s_.lastIndexOf(' '));
    } else {
        s_ = this;
    }
    return s_ + "…";
}