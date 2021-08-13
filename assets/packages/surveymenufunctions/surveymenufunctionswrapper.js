var SurveyMenuFunctionsWrapper = function (targetCreateModal, targetGrid, urls) {

    var _editCreateModal = function (callback, postData) {
        postData = postData || {};
        var modalContent = $(targetCreateModal).find('.modal-content');
        modalContent.load(urls.loadSurveyEntryFormUrl, postData, function (response, status, xhr) {
            modalContent.find('form').on('submit', function (evt) {
                evt.preventDefault();
                var data = $(this).serializeArray();
                var url = $(this).attr('action');
                $.ajax({
                    url: url,
                    data: data,
                    method: 'POST',
                    dataType: 'json',
                    success: callback,
                    error: function (error) {
                        console.log(error);
                    }
                });
            });
        });
        $(targetCreateModal).modal('show');
    },
    runCreateModal =  function () {
        return _editCreateModal(
            function (data) {
                $(targetCreateModal).modal('hide');
                $.fn.yiiGridView.update(targetGrid);
            }
        );
    },
    runEditModal =  function (postData) {
        return _editCreateModal(
            function (data) {
                $(targetCreateModal).modal('hide');
                $.fn.yiiGridView.update(targetGrid);
            },
            postData
        );
    },
        /**
         * This function works with two different modals, doing the same for survemenu
         * and surveymenuentries
         *
         * @param idDeleteModal  id of the modal
         * @param postData  data for ajax request
         * @param idDeleteBtn id of the delete btn in the modal
         */
    runDeleteModal =  function (idDeleteModal, postData,idDeleteBtn) {
            idDeleteModal.modal('show');
            idDeleteModal.on('shown.bs.modal', function () {
                idDeleteBtn.on('click', function () {
                $.ajax({
                    url: urls.deleteEntryUrl,
                    data: postData,
                    method: 'post',
                    success: function (data) {
                        window.location.reload();
                    },
                    error: function (err) {
                        window.location.reload();
                    }
                });
            });
        });
    },
    runReorderEntries = function(){
        $.ajax({
            url: urls.reorderEntriesUrl,
            data: {},
            method: 'POST',
            dataType: 'json',
            success: function (result) {
                $.fn.yiiGridView.update(targetGrid);
            },
            error: function (error) {
                console.log(error);
            }
        });
    },
    runRestoreModal =  function (urlMenu, urlMenuEntry) {
        $('#restoremodalsurveymenu').find('.modal-content').html('<div ' + 'class="ls-flex align-items-center align-content-center" style="height:200px"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>')
        //url is depending on which tab is active
        let active_tab = $('#menueslist li.active a').attr('href');
        var urlRestore = '';
        if (active_tab === '#surveymenues') {
            urlRestore = urlMenu;
        } else if (active_tab === '#surveymenuentries') {
            urlRestore = urlMenuEntry;
        }
        $.ajax({
            url: urlRestore,
            data: {},
            method: 'POST',
            dataType: 'json',
            success: function (result) {
                $('#restoremodalsurveymenu').find('.modal-content').html('<div class="ls-flex align-items-center align-content-center" style="height:200px">' + result.message + '</div>');

                if (result.success)
                    setTimeout(function () {
                        window.location.reload();
                    }, 1500);
            }
        });
    };

    $('#restoreBtn').on('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $('#restoremodalsurveymenu').modal('show');
    });

    $('#reset-menus-confirm').on('click', function (e) {
        e.preventDefault();

        runRestoreModal($(this).attr('data-urlmenu'), $(this).attr('data-urlmenuentry'));
    });

    return {
        getBindActionForSurveymenuEntries : function () {
            return function () {
        
                $('#createnewmenuentry').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    runCreateModal();
                });
        
                $(targetCreateModal).on('hidden.bs.modal', function () {
                    $(this).find('.modal-content').html('');
                });
        
                $('#surveymenu-entries-grid').on('click', 'tr', function () {
                    $(this).find('.action_selectthisentry').prop('checked', !$(this).find('.action_selectthisentry').prop('checked'));
                });
                $('.action_selectthisentry').on('click', function (e) {
                    e.stopPropagation();
                });
                
                $('#reorderentries').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    runReorderEntries();
                });
        
                $('.action_surveymenuEntries_editModal').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    runEditModal({
                        menuentryid: $(this).closest('tr').data('surveymenu-entry-id'),
                        ajax: true
                    });
                });
        
                $('.action_surveymenuEntries_deleteModal').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var idDeleteModal = $('#deletemodal');
                    var idDeleteModalBtn = $('#deletemodalentry-confirm');
                    runDeleteModal(idDeleteModal,{
                        menuEntryid: $(this).closest('tr').data('surveymenu-entry-id'),
                        ajax: true
                    }, idDeleteModalBtn);
                });

                $('#pageSize').on("change", function(e){
                    console.log('pageSizeChanged', $(this).val());
                    $.fn.yiiGridView.update(targetGrid,{ data:{ pageSize: $(this).val() }});
                });
            };
        },
        getBindActionForSurveymenus : function () {
            return function () {
        
                $('#createnewmenu').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    runCreateModal();
                });
        
                $('#editcreatemenu').on('hidden.bs.modal', function () {
                    $(this).find('.modal-content').html('');
                });
        
                $('#surveymenu-grid').on('click', 'tr', function () {
                    $(this).find('.action_selectthismenu').prop('checked', !$(this).find('.action_selectthismenu').prop('checked'));
                });
                $('.action_selectthismenu').on('click', function (e) {
                    e.stopPropagation();
                });
        
                $('.action_surveymenu_editModal').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    runEditModal({
                        menuid: $(this).closest('tr').data('surveymenu-id'),
                        ajax: true
                    });
                });
        
                $('.action_surveymenu_deleteModal').on('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var idDeleteModal = $('#deletesurveymenumodal');
                    var idDeleteModalBtn = $('#deletemodal-confirm');
                    runDeleteModal(idDeleteModal,{
                        menuid: $(this).closest('tr').data('surveymenu-id'),
                        ajax: true
                    },idDeleteModalBtn);
                });

                $('#pageSize').on("change", function(e){
                    console.log('pageSizeChanged', $(this).val());
                    $.fn.yiiGridView.update(targetGrid,{ data:{ pageSize: $(this).val() }});
                });
            }
        }
    }
};

