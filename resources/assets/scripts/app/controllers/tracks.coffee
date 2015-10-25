window.pfm.preloaders['tracks'] = [
    'tracks', '$state'
    (tracks) ->
        tracks.loadFilters()
]

angular.module('ponyfm').controller "tracks", [
    '$scope', 'tracks', '$state'
    ($scope, tracks, $state) ->
        $scope.recentTracks = null
        $scope.query = tracks.mainQuery
        $scope.filters = tracks.filters

        $scope.toggleListFilter = (filter, id) ->
            $scope.query.toggleListFilter filter, id
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.setFilter = (filter, value) ->
            $scope.query.setFilter filter, value
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.setListFilter = (filter, id) ->
            $scope.query.setListFilter filter, id
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.clearFilter = (filter) ->
            $scope.query.clearFilter filter
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        tracks.mainQuery.listen (searchResults) ->
            $scope.tracks = searchResults.tracks
            $scope.currentPage = parseInt(searchResults.current_page)
            $scope.totalPages = parseInt(searchResults.total_pages)
            delete $scope.nextPage
            delete $scope.prevPage

            $scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
            $scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
            $scope.pages = [1..$scope.totalPages]

        $scope.gotoPage = (page) ->
            $state.transitionTo 'content.tracks.list', {filter: $state.params.filter, page: page}

        $scope.$on '$destroy', -> tracks.mainQuery = tracks.createQuery()
]
