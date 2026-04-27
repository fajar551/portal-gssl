<div class="card" style="margin-bottom: 0.5rem;">
    <div class="card-body table-responsive">
        <table id="tbl_allDoc" class="table table-hover table-striped table-bordered">
            <thead class="bg-dark text-white">
                <tr>
                    <th>Client</th>
                    <th>Total File</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($all_documents as $row)
                    <tr>
                        <td>
                            <b><i class="glyphicon glyphicon-user"></i> {{ e($row->firstname) ?? 'N/A' }} |
                                <i class="glyphicon glyphicon-home"></i> {{ e($row->companyname) ?? 'N/A' }}</b>
                            <blockquote class="mb-0" style="font-size:12px">
                                <p><i class="glyphicon glyphicon-envelope"></i> {{ e($row->email) ?? 'N/A' }}</p>
                                <p><i class="glyphicon glyphicon-phone"></i> {{ e($row->phonenumber) ?? 'N/A' }}</p>
                            </blockquote>
                        </td>
                        <td class="text-center">{{ $row->jumlah ?? 0 }} Files</td>
                        <td class="text-center">
                            <a href="{{ url('admin/addonsmodule?module=privatensregistrar&page=document_client&userid=' . $row->id) }}"
                                class="btn btn-sm btn-primary">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>