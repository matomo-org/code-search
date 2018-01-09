var app = angular.module('githubSearch', ['ngSanitize', 'hljs']);

app.controller('SearchController', function($scope, $http) {
    $scope.loading = false;
    $scope.error = false;
    $scope.includePiwik = true;

    $scope.search = function() {
        $scope.loading = true;
        $http.get('/api/search?includePiwik=' + ($scope.includePiwik + 0) + '&q=' + $scope.expression).
            then(function(data) {
                console.warn(data);
                $scope.results = data.data;
                $scope.error = false;
                $scope.loading = false;
            }, function() {
                $scope.error = true;
                $scope.loading = false;
            });
    };

});
