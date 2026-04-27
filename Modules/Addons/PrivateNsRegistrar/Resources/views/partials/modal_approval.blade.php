<!-- Modal Sync TLD -->
<div class="modal fade" id="modalsync" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Sync TLD</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-center"><b>Are you sure you want to sync?</b></p>
            </div>
            <div class="modal-footer">
                <button type="button" id="synctld" class="btn btn-info" data-dismiss="modal">OK</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Process Document -->
<div id="modalApproval" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title slide-in-left">Dokumen "<span id="domain_name"></span>"</h4>
                <button type="button" class="close _reload" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="loader" class="text-center mb-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading...</p>
                </div>
                <div id="documentContent"></div>
            </div>
        </div>
    </div>
</div>
