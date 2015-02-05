'use strict';

angular.module('myApp.filters', []).filter('stripAACR2Punctuation', function () {
    return function (input) {
        return (typeof input === "string") ? input.replace(/\.$/, '') : input;
    };
});