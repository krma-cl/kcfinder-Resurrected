/**
 * Folder and file name search.
 *
 * The search endpoint returns a reduced folder tree. Clearing the query reloads
 * the normal lazy tree so its current directory and permissions remain current.
 */

_.initSearch = function () {
    var input = $('#folderSearchInput');

    if (!_.search.enabled || !input.length)
        return;

    input.off('.kcfSearch')
        .on('input.kcfSearch', function () {
            _.scheduleSearch($(this).val());
        })
        .on('keydown.kcfSearch', function (event) {
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault();
                _.scheduleSearch($(this).val(), true);
            } else if (event.key === 'Escape' || event.keyCode === 27) {
                event.preventDefault();
                _.clearSearch(true);
            }
        });

    $('#folderSearchClear').off('.kcfSearch').on('click.kcfSearch', function () {
        _.clearSearch(true);
    });
};

_.scheduleSearch = function (query, immediate) {
    query = $.trim(query || '');
    clearTimeout(_.searchTimer);
    if (_.searchRequest) {
        _.searchRequest.abort();
        _.searchRequest = null;
    }

    if (query.length < _.search.minChars) {
        if (_.searchActive)
            _.restoreSearchTree();
        else
            _.searchStatus('');
        return;
    }

    if (immediate)
        _.runSearch(query);
    else
        _.searchTimer = setTimeout(function () {
            _.runSearch(query);
        }, _.search.debounceMs);
};

_.runSearch = function (query) {
    var requestedQuery = query,
        request;

    if (_.searchRequest)
        _.searchRequest.abort();

    _.searchStatus(_.label("Searching..."));
    request = $.ajax({
        type: 'post',
        dataType: 'json',
        url: _.getURL('search'),
        data: {
            csrf_token: csrfToken,
            query: query
        },
        success: function (data) {
            if ($.trim($('#folderSearchInput').val()) !== requestedQuery)
                return;
            if (_.check4errors(data)) {
                _.searchStatus('');
                return;
            }

            if (!_.searchActive || !_.searchOriginalFiles)
                _.searchOriginalFiles = _.files.slice(0);
            _.searchActive = true;
            _.searchQuery = requestedQuery;
            if (!data.tree || !data.resultCount) {
                $('#folders').html('<div class="search-empty">' + $.$.htmlData(_.label("No matching folders.")) + '</div>');
            } else {
                $('#folders').html(_.buildTree(data.tree));
                _.setTreeData(data.tree);
                _.initFolders();
                _.initDropUpload();
            }

            if (data.truncated)
                _.searchStatus(_.label("Partial results: {count} matching folders.", {count: data.resultCount}));
            else
                _.searchStatus(_.label("{count} matching folders.", {count: data.resultCount}));
            _.files = _.filterSearchFiles(_.searchOriginalFiles);
            _.orderFiles();
            _.fixFoldersHeight();
            _.fixScrollRadius();
        },
        error: function (request, status) {
            if (status !== 'abort')
                _.searchStatus(_.label("Unknown error."));
        },
        complete: function () {
            if (_.searchRequest === request)
                _.searchRequest = null;
        }
    });
    _.searchRequest = request;
};

_.clearSearch = function (focus) {
    clearTimeout(_.searchTimer);
    if (_.searchRequest)
        _.searchRequest.abort();

    $('#folderSearchInput').val('');
    if (_.searchActive)
        _.restoreSearchTree();
    else
        _.searchStatus('');

    if (focus)
        $('#folderSearchInput').get(0).focus();
};

_.restoreSearchTree = function () {
    _.searchActive = false;
    _.searchQuery = '';
    _.searchOriginalFiles = null;
    _.searchStatus('');
    _.initContent();
    _.fixFoldersHeight();
    _.fixScrollRadius();
};

_.searchStatus = function (message) {
    $('#folderSearchStatus').text(message || '');
};

_.filterSearchFiles = function (files) {
    var query = (_.searchQuery || '').toLocaleLowerCase(),
        path = (_.dir || '').replace(/\/+$/, ''),
        directoryName = path.substr(path.lastIndexOf('/') + 1).toLocaleLowerCase();

    if (!query.length || directoryName.indexOf(query) !== -1)
        return files.slice(0);

    return $.grep(files, function (file) {
        return (file.name || '').toLocaleLowerCase().indexOf(query) !== -1;
    });
};
