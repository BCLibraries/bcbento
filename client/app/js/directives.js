(function () {
    angular.module('myApp.directives', [])
        .directive('searchbox', [function () {
            return {
                restrict: 'AE',
                templateUrl: '/search/partials/searchbox.html'
            }
        }])
        .directive('results',[function() {
            return {
                restrict: 'AE',
                templateUrl: '/search/partials/results.html'
            }
        }]);
})();