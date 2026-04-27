@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - All Auction Backorder</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h2 class="mb-0">Backorder Auction</h2>
                        <small class="text-muted">List lelang backorder</small>
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

                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped wrap" id="backorderTable">
                                        <thead>
                                          <tr>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Service Name</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Registrar Day</th>
                                            <th scope="col">Expiration Day</th>
                                            <th scope="col">Buyback Day</th>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">PA</th>
                                            <th scope="col">DA</th>
                                            <th scope="col">Backlinks</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          @foreach($backorder as $val)
                                          <tr>
                                            <td>{{ $val->name ?? '-' }}</td>
                                            <td>{{ $val->email ?? '-' }}</td>
                                            <td>{{ $val->service_name ?? '-' }}</td>
                                            <td>{{ $val->description ?? '-' }}</td>
                                            <td>{{ $val->registrarday ?? '-' }}</td>
                                            <td>{{ $val->expirationday ?? '-' }}</td>
                                            <td>{{ $val->buybackday ?? '-' }}</td>
                                            <td>{{ $val->tanggal ?? '-' }}</td>
                                            <td>{{ $val->status ?? '-' }}</td>
                                            <td>{{ $val->page_authority ?? '-' }}</td>
                                            <td>{{ $val->domain_authority ?? '-' }}</td>
                                            <td>{{ $val->backlinks ?? '-' }}</td>
                                          </tr>
                                          @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/addonsmodule?module=auction') }}'">
                    <i class="fas fa-arrow-left"></i> Back To Previous Page 
                </button>
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
        $(document).ready( function () {
            $('#backorderTable').DataTable({
                "order": [[ 4, "desc" ]]
            });
        });
    </script>
@endsection