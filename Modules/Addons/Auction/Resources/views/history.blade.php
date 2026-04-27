@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - My Auction</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h2 class="mb-0">History Auction</h2>
                        <small class="text-muted">Lelang Domain Saya</small>
                    </div>
                    {{-- alert message --}}
                    <div class="col-md-12">
                        @if (Session::has('alert-message'))
                            <div class="alert alert-{{ Session::get('alert-type') }}" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {!! nl2br(e(Session::get('alert-message'))) !!}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <b>Error:</b>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    {{-- alert message --}}

                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Id</th>
                                            <th scope="col">Domain</th>
                                            <th scope="col">Client ID</th>
                                            <th scope="col">Bid Price</th>
                                            <th scope="col">Last Price</th>
                                            <th scope="col">Invoice ID</th>
                                            <th scope="col">Updated At</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($all_history as $val)
                                        <tr>
                                            <th scope="row">{{ $val->id }}</th>
                                            <td><a target="_blank" href="http://{{ $val->domain }}">{{ $val->domain }}</a></td>
                                            <td><a target="_blank" href="{{ url('admin/clients/clientssummary?userid=' . $val->client_id) }}">{{ $val->client_id }}</a></td>
                                            <td>{{ 'Rp ' . number_format($val->bid_price, 0, ',', '.') }}</td>
                                            <td>{{ 'Rp ' . number_format($val->last_price, 0, ',', '.') }}</td>
                                            <td><a target="_blank" href="/qwadmin/invoices.php?action=edit&id={{ $val->invoiceid }}">{{ $val->invoiceid }}</a></td>
                                            <td>{{ $val->updated_at }}</td>
                                            <td>
                                                <div class="input-group mb-3">
                                                    <input type="number" class="form-control" value="{{ $val->bid_price }}" id="bid-price-{{ $val->id }}">
                                                    <div class="input-group-append">
                                                        <button 
                                                            onclick="handleUpdatePrice(this)" 
                                                            data-domain="{{ $val->domain }}" 
                                                            data-id="{{ $val->id }}" 
                                                            data-bid-price="{{ $val->bid_price }}" 
                                                            class="btn btn-outline-secondary">
                                                            Save
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/addonsmodule?module=auction') }}'">
                        <i class="fas fa-arrow-left"></i> Back To Previous Page 
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmUpdateModal" tabindex="-1" role="dialog" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmUpdateModalLabel">Confirm Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to update the price for domain ID: <span id="domainId"></span>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmUpdateButton">Confirm</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.table').DataTable({
            "order": [[6, "desc"]]
        });
    });

    function handleUpdatePrice(el) {
        var domain = $(el).data('domain');
        var id_bid = $(el).data('id');
        var value = $('#bid-price-' + id_bid).val();

        // Set the domain ID in the modal
        $('#domainId').text(id_bid);

        // Show the modal
        $('#confirmUpdateModal').modal('show');

        // Handle the confirmation button click
        $('#confirmUpdateButton').off('click').on('click', function() {
            var url = '{{ route('addons.auction.action', ['action' => 'save_bid']) }}' + 
                    '&domain=' + encodeURIComponent(domain) + 
                    '&id_bid=' + encodeURIComponent(id_bid) + 
                    '&value_bid=' + encodeURIComponent(value);

            // Redirect to the URL
            location.href = url;
        });
    }
</script>
@endsection