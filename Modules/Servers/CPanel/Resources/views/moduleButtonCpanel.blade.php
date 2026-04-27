<div class="form-group row">
    <label for="#" class="col-sm-2 col-form-label">Module Commands</label>
    <div class="col-sm-10">
        <button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('Create')">
            Create
        </button>

        <!--<button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('Renew')">-->
        <!--    Renew-->
        <!--</button>-->

        <button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('Suspend')">
            Suspend
        </button>

        <button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('Unsuspend')">
            Unsuspend
        </button>

        <button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('Terminate')">
            Terminate
        </button>

        <button type="button" class="btn btn-light my-1 mx-1" 
    onclick="moduleCommand('changepackage')"
    data-package="{{ $service->product->name ?? 'default' }}"
    data-userid="{{ $userid }}"
    data-id="{{ $id }}">
    Change Package
</button>

        <button type="button" class="btn btn-light my-1 mx-1" data-toggle="modal"
                    data-target="#changePasswordModal">
                    Change Password
                </button>

        <!--<button type="button" class="btn btn-light my-1 mx-1" onclick="modCommand('ManageAppLinks')">-->
        <!--    Manage App Links-->
        <!--</button>-->
    </div>
</div>