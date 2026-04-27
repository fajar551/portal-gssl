@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - Lelang Domain</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h2 class="mb-0">Lelang Domain</h2>
                        <small class="text-muted">By CBMS</small>
                    </div>
                    {{-- alert message --}}
                    <div class="col-md-12">
                        @if (Session::get('alert-message'))
                            <div class="alert alert-{{ Session::get('alert-type') }}" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {!! nl2br(Session::get('alert-message')) !!}
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

                    {{-- <button onclick="window.location.href='{{ url('/admin/addonsmodule?module=auction&page=backorder') }}'" class="btn btn-secondary">
                        << Back To Home 
                    </button> --}}
                    <div class="col-md-12">
                        <form action="{{ url('/admin/addonsmodule') }}" method="GET" style="display:inline;">
                            <input type="hidden" name="module" value="auction">
                            <input type="hidden" name="page" value="backorder">
                            <button type="submit" class="btn btn-secondary">
                                Backorder Page
                            </button>
                        </form>   
                    </div>
                    
                    {{-- search and filter --}}
                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <div id="accordion" class="custom-accordion mt-1 pb-1">
                                    <div class="card mb-1 shadow-none">
                                        <a href="#collapseOne" class="text-dark" data-toggle="collapse" aria-expanded="true"
                                            aria-controls="collapseOne">
                                            <div class="card-header" id="headingOne">
                                                <h6 class="m-0">
                                                    Search & Filter
                                                    <i class="mdi mdi-minus float-right accor-plus-icon"></i>
                                                </h6>
                                            </div>
                                        </a>
                                        
                                        <div id="collapseOne" class="collapse hide" aria-labelledby="headingOne" data-parent="#accordion">
                                            <div class="card-body">
                                                <form action="{{ route('addons.auction.insertAuction') }}" method="POST" id="form-filters" enctype="multipart/form-data" onsubmit="return filterTable(this)">
                                                    @csrf 
                                                    <div class="row">
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label for="search_domain" class="text-truncate mb-0">Input domain lelang manual</label>
                                                                <input type="text" id="domain" name="domain" class="form-control" placeholder="Domain"> 
                                                                <input type="hidden" id="type" name="type" class="form-control" value="redirect">
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label for="filter_status">Filter Status</label>
                                                                <select id="filter_status" name="filter_status" class="form-control">
                                                                    <option value="all"> all </option>
                                                                    <option value="open_lelang_backorder"> open_lelang_backorder</option>
                                                                    <option value="invoice_create"> invoice_create</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 text-right">
                                                            <div class="form-group">
                                                                <button type="submit" class="btn btn-primary">Redirect</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>

                                                <form id="filterForm">
                                                    <input type="hidden" name="isFilter" value="true">
                                                    <input type="hidden" name="module" value="auction">
                                                    <div class="row">
                                                        <div class="col-md-3 mb-3">
                                                            <input type="text" name="domain" class="form-control" placeholder="Domain">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <input type="number" name="price" class="form-control" placeholder="Price">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <input type="date" name="open_date" class="form-control" placeholder="Open Date">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <input type="date" name="close_date" class="form-control" placeholder="Close Date">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <select name="status" class="form-control">
                                                                <option value="">Status</option>
                                                                <option value="OPEN_LELANG">OPEN_LELANG</option>
                                                                <option value="SELL_DOMAIN">SELL_DOMAIN</option>
                                                                <option value="ARCHIEVED">ARCHIEVED</option>
                                                                <option value="CLOSE_LELANG">CLOSE_LELANG</option>
                                                                <option value="INVOICE_CREATED">INVOICE_CREATED</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <input type="text" name="owner" class="form-control" placeholder="Owner">
                                                        </div>
                                                        <div class="col-md-3 mb-3 ml-auto text-right">
                                                            <button type="button" class="btn btn-primary" id="filterButton">Filter</button>
                                                            <button type="button" class="btn btn-secondary" id="clearButton">Clear</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- search and filter --}}

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5>Penjualan Hari Ini</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped wrap" id="history">
                                        <thead>
                                            <tr>
                                                <th scope="col">Id</th>
                                                <th scope="col">Domain</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Open Date</th>
                                                <th scope="col">Close Date</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Maxtry</th>
                                                <th scope="col">Owner</th>
                                                <th scope="col">Updated At</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($all_auction as $val)
                                                <tr>
                                                    <th scope="row">{{ $val->id }}</th>
                                                    <td><a target="_blank" href="http://{{ $val->domain }}">{{ $val->domain }}</a></td>
                                                    <td>
                                                        @if (boolval($val->price))
                                                            Rp{{ number_format($val->price, 0, ',', '.') }}
                                                        @else
                                                            <a href="{{ url('admin/addonsmodule') . '?module=auction&domain=' . urlencode($val->domain) . '&page=history' }}" target="_blank"> see history </a>
                                                        @endif
                                                    </td>
                                                    <td>{{ $val->open_date }}</td>
                                                    <td>{{ $val->close_date }}</td>
                                                    <td>
                                                        @php
                                                            $status = $val->status;
                                                            $badgeClass = '';

                                                            switch ($status) {
                                                                case 'OPEN_LELANG':
                                                                    $badgeClass = 'badge-primary';
                                                                    $status = '_OPEN_LELANG';
                                                                    break;
                                                                case 'SELL_DOMAIN':
                                                                    $badgeClass = 'badge-success';
                                                                    break;
                                                                case 'ARCHIEVED':
                                                                    $badgeClass = 'badge-secondary';
                                                                    break;
                                                                case 'CLOSE_LELANG':
                                                                    $badgeClass = 'badge-danger';
                                                                    break;
                                                                case 'INVOICE_CREATED':
                                                                    $badgeClass = 'badge-warning';
                                                                    break;
                                                                default:
                                                                    $badgeClass = 'badge-light';
                                                                    break;
                                                            }
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                                    </td>
                                                    <td>{{ $val->maxtry }}</td>
                                                    <td>
                                                        <a target="_blank" href="{{ url('admin/clients/clientssummary?userid=' . $val->owner) }}">{{ $val->owner }}</a>
                                                    </td>
                                                    <td>{{ $val->updated_at }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-center flex-wrap">
                                                            <a class="btn btn-primary m-1" href="{{ url('admin/addonsmodule?module=auction&domain=' . urlencode($val->domain) . '&page=history') }}" target="_blank">see history</a>
                                                            @if ($val->status !== 'OPEN_LELANG')
                                                                <button class="btn btn-secondary m-1" onclick="showReopenModal({{ $val->id }})">Re-Open</button>
                                                                <button class="btn btn-success m-1" onclick="showRestartModal({{ $val->id }})">Re-Start</button>
                                                            @endif
                                                            <button class="btn btn-danger m-1" onclick="showConfirmModal({{ $val->id }}, this)">Archive</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
    </div>

    {{-- modal button-restart --}}
    <div id="restart" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Re Start Lelang</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <p>Dengan melakukan restart auction, maka semua data history auction ini akan dipindahkan ke table auction_history_old.</p>
              <div class="form-group">
                  <label for="harga-awal">Harga Awal (Rp)</label>
                  <input class="form-control" type="number" id="harga-awal" name="hargaawal" >
              </div>
              <div class="form-group">
                  <label for="dtpick-restart">Close Date</label>
                  <input class="form-control" type="datetime-local" id="dtpick-restart" name="dtpick" >
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" onclick="prosesReStart()">Proses</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    {{-- modal button-restart --}}
    
    {{-- modal button-reopen --}}
      <div id="reopen" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Re Open Lelang</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                  <label for="dtpick">Close Date</label>
                  <input class="form-control" type="datetime-local" id="dtpick" name="dtpick" >
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" onclick="prosesReOpen()">Proses</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    {{-- modal button-reopen --}}

    <!-- Modal Konfirmasi -->
    <div id="confirmModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Apakah Anda yakin?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmYes">Ya</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tidak</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Archive</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Are you sure you want to archive this auction?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmYes">Yes, Archive</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ Theme::asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ Theme::asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
    <script src="{{ Theme::asset('js/notify.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    
    <script>
        var table; // Declare table globally

        $(document).ready(function () {
            table = $('.table').DataTable({
                "order": [[8, "desc"]],
                "columns": [
                    { "data": "id" },
                    { "data": "domain" },
                    { "data": "price" },
                    { "data": "open_date" },
                    { "data": "close_date" },
                    {
                        "data": "status",
                        "render": function (data, type, row) {
                            let badgeClass = '';
                            let statusText = data;

                            switch (data) {
                                case 'OPEN_LELANG':
                                    badgeClass = 'badge-primary';
                                    statusText = '_OPEN_LELANG';
                                    break;
                                case 'SELL_DOMAIN':
                                    badgeClass = 'badge-success';
                                    break;
                                case 'ARCHIEVED':
                                    badgeClass = 'badge-secondary';
                                    break;
                                case 'CLOSE_LELANG':
                                    badgeClass = 'badge-danger';
                                    break;
                                case 'INVOICE_CREATED':
                                    badgeClass = 'badge-warning';
                                    break;
                                default:
                                    return statusText; 
                            }

                            return `<span class="badge ${badgeClass}">${statusText}</span>`;
                        }
                    },
                    { "data": "maxtry" },
                    { "data": "owner" },
                    { "data": "updated_at" },
                    {
                        "data": null,
                        "render": function (data, type, row) {
                            // Ensure row.domain is a clean string
                            const domain = row.domain.replace(/<[^>]*>?/gm, ''); // Remove any HTML tags

                            return `
                                <div class="d-flex justify-content-center flex-wrap">
                                    <a class="btn btn-primary m-1" href="https://my.hostingnvme.id/admin/addonsmodule?module=auction&domain=${encodeURIComponent(domain)}&page=history" target="_blank">see history</a>
                                    ${row.status !== 'OPEN_LELANG' ? `
                                        <button class="btn btn-secondary m-1" onclick="showReopenModal(${row.id})">Re-Open</button>
                                        <button class="btn btn-success m-1" onclick="showRestartModal(${row.id})">Re-Start</button>
                                    ` : ''}
                                    <button class="btn btn-danger m-1" onclick="showConfirmModal(${row.id}, this)">Archive</button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            $('#filter_status').change(function(){
                table.search('').columns().search('').draw();
                if (this.value == 'open_lelang_backorder'){
                    table
                        .column(7)
                        .search( '^$', true, false )
                        .draw();
                        
                    table
                        .column(5)
                        .search('open_lelang')
                        .draw();
                } else if (this.value == 'invoice_create'){
                    table
                        .column(5)
                        .search('invoice_created')
                        .draw();
                }
            })

            $('#filterButton').on('click', function () {
                $.ajax({
                    url: '{{ route('addons.auction.index') }}',
                    method: 'GET',
                    data: $('#filterForm').serialize(),
                    success: function (data) {
                        // console.log('Filtered Data:', data); // Log the data to verify structure
                        table.clear().rows.add(data).draw();
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });
            });

            $('#clearButton').on('click', function () {
                $('#filterForm')[0].reset();
                table.search('').columns().search('').draw();
            });
        });

        function showReopenModal(id) {
            $('#reopen').data('id', id).modal('show');
        }

        function showRestartModal(id) {
            $('#restart').data('id', id).modal('show');
        }

        function prosesReOpen() {
            var id = $('#reopen').data('id');
            var datetime = $('#dtpick').val();

            $.ajax({
                url: '{{ route('addons.auction.action') }}',
                method: 'POST',
                data: {
                    id_domain: id,
                    action: 'reopen',
                    datetime: datetime,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Auction reopened successfully.');
                        $('#reopen').modal('hide');
                        table.clear().rows.add(data).draw();
                        // Optionally, refresh the DataTable or update the specific row
                    } else {
                        alert('Failed to reopen auction: ' + response.description);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error reopening auction:', error);
                    alert('An error occurred while reopening the auction.');
                }
            });
        }

        function prosesReStart() {
            var id = $('#restart').data('id');
            var datetime = $('#dtpick-restart').val();
            var hargaawal = $('#harga-awal').val();

            $.ajax({
                url: '{{ route('addons.auction.action') }}',
                method: 'POST',
                data: {
                    id_domain: id,
                    action: 'restart',
                    datetime: datetime,
                    hargaawal: hargaawal,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status === 'success') {
                        alert('Auction restarted successfully.');
                        $('#restart').modal('hide');
                        table.clear().rows.add(data).draw();
                        // Optionally, refresh the DataTable or update the specific row
                    } else {
                        alert('Failed to restart auction: ' + response.description);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error restarting auction:', error);
                    alert('An error occurred while restarting the auction.');
                }
            });
        }

        window.addEventListener('load', () => {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('dtpick').value = now.toISOString().slice(0, -1);
        });

        function showConfirmModal(id, button) {
            $('#confirmYes').off('click').on('click', function() {
                handleArchive(id, button);
                $('#confirmModal').modal('hide');
            });
            $('#confirmModal').modal('show');
        }

        $('#confirmModal').on('hidden.bs.modal', function () {
            $('#mainContent').removeAttr('inert');
        });

        function handleArchive(id, button) {
            $.ajax({
                url: '{{ route('addons.auction.action') }}',
                method: 'POST',
                data: {
                    id_domain: id,
                    action: 'delete',
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status === 'success') {
                        // Remove the row from the DataTable
                        var row = $(button).closest('tr');
                        table.row(row).remove().draw();
                        alert('Auction archived successfully.');
                    } else {
                        alert('Failed to archive auction: ' + response.description);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error archiving auction:', error);
                    alert('An error occurred while archiving the auction.');
                }
            });
        }
    </script>
@endsection
