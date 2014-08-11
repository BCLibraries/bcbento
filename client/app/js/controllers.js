'use strict';

/* Controllers */
(function () {

    angular.module('myApp.controllers', []).
        controller('bento', ['$http', '$scope', '$location',

            function ($http, $scope, $location) {

                var base_url, search, search_services;

                // Add new services here.
                search_services = ['catalog', 'articles', 'dpla', 'services', 'guides'];

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
                    $http.jsonp(base_url + search_service + search + '&callback=JSON_CALLBACK', {'cache': true}).success(
                        function (data) {
                            console.log(search_service);
                            console.log(data);
                            $scope[search_service + '_results'] = data;
                        }
                    ).error(
                        function (data, status) {
                            console.log("Error: " + status);
                        }
                    );
                }
            }])
        .controller('AutoComplete', ['$scope', '$http',
            function ($scope, $http) {
                $scope.search = function ($item, $model, $label) {
                    document.getElementById('searchbox').value = $item.text;
                    search();
                }

                $scope.getLocation = function (val) {
                    return $http.jsonp('/search-services/suggest?callback=JSON_CALLBACK&text=' + val).then(function (res) {

                        // Autopopulate first item
                        var first_item = document.getElementById('searchbox').value;

                        var suggestions = [
                            {text: first_item, payload: {type: ""}}
                        ];
                        angular.forEach(res.data.ac[0].options, function (item) {
                            suggestions.push(item);
                        });
                        console.log(suggestions);
                        return suggestions;
                    });
                };

            }])
        .controller('catalog', ['$scope',
            function ($scope) {

            }]);

    String.prototype.truncate = function (max_length) {
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
})();
