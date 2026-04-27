<div class="card" style="margin-bottom: 0.5rem;">
    <div class="card-body table-responsive">
        <table class="table table-hover table-striped table-bordered">
            <thead class="bg-dark text-white text-center">
                <tr>
                    <th>Domain</th>
                    <th>Client</th>
                    <th>File Need Approve</th>
                    <th>Action</th>
                </tr>
            </thead>
                <tbody>
                    @foreach ($approval as $t)
                        <tr>
                            <td>{{ $t->domain }}</td>
                            <td>
                                <b>{{ $t->client_name }}</b>
                                <p class="mb-0">({{ $t->client_email }})</p>
                            </td>
                            <td class="text-center">{{ $t->file }} File Need Approve</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary btn-approval" data-toggle="modal"
                                    data-target="#modalApproval" data-id="{{ $t->domain }}">
                                    Process
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>