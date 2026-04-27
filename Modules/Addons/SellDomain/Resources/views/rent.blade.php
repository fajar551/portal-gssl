@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - Rental Domain</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h2 class="mb-0">Rental Domain</h2>
                        <small class="text-muted">By CBMS</small>
                    </div>
                    {{-- Alert Messages --}}
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
                    {{-- Alert Messages --}}
                    {{-- Search and Filter --}}
                    <div class="col-md-12">
                        <div class="accordion" id="accordionExample">
                            <div class="card">
                                <div class="card-body">
                                    <div id="accordion" class="custom-accordion mt-1 pb-1">
                                        <div class="card mb-1 shadow-none">
                                            <a href="#collapseOne" class="text-dark" data-toggle="collapse"
                                                aria-expanded="true" aria-controls="collapseOne">
                                                <div class="card-header" id="headingOne">
                                                    <h6 class="m-0">
                                                        Search & Filter
                                                        <i class="mdi mdi-minus float-right accor-plus-icon"></i>
                                                    </h6>
                                                </div>
                                            </a>

                                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                                                data-parent="#accordionExample">
                                                <div class="card-body">
                                                    <form id="searchFilterForm" action="{{ route('addons.selldomain.index') }}" method="GET">
                                                        <input type="hidden" name="module" value="selldomain" />
                                                        <input type="hidden" name="isRent" value="true" />
                                                        <div class="form-group">
                                                            <label>Status</label>
                                                            <select name="status" class="form-control">
                                                                <option value="">Pilih</option>
                                                                <option value="NEED_VERIFY">NEED_VERIFY</option>
                                                                <option value="VERIFIED">ON_SELLING</option>
                                                                <option value="INVOICE_PAID">INVOICE_PAID</option>
                                                                <option value="PROCESS_TRANSFER">PROCESS_TRANSFER</option>
                                                                <option value="SETTLED">SETTLED</option>
                                                                <option value="RENT_ACTIVE">RENT_ACTIVE</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Domain</label>
                                                            <input type="text" name="domain" class="form-control"
                                                                placeholder="example.com">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Price / Harga</label>
                                                            <input type="text" name="price" class="form-control"
                                                                placeholder="1000000">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>EPP</label>
                                                            <input type="text" name="epp" class="form-control"
                                                                placeholder="Code EPP">
                                                        </div>
                                                        <button class="btn btn-primary" type="submit">Set Filter</button>
                                                        <button type="button" class="btn btn-danger" id="clearFormButton">Clear</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- DataTable --}}
                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Penjualan Hari Ini</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped wrap" id="dtable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Domain</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">ClientID</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">EPP</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($domain_rent as $data)
                                                <tr>
                                                    <td>{{ $data->domain }}</td>
                                                    <td>{{ $data->price }}</td>
                                                    <td>{{ $data->clientid }}</td>
                                                    <td>
                                                        @if ($data->status)
                                                            @switch($data->status)
                                                                @case('PROCESS_TRANSFER')
                                                                    <span class="badge badge-warning">PROCESS_TRANSFER</span>
                                                                @break

                                                                @case('RENT_ACTIVE')
                                                                    <span class="badge badge-success">RENT_ACTIVE</span>
                                                                @break

                                                                @case('SETTLED')
                                                                    <span class="badge badge-success">SETTLED</span>
                                                                @break

                                                                @case('VERIFIED')
                                                                    <span class="badge badge-success">VERIFIED</span>
                                                                @break

                                                                @case('NEED_VERIFY')
                                                                    <span class="badge badge-info">NEED_VERIFY</span>
                                                                @break

                                                                @case('INVOICE_PAID')
                                                                    <span class="badge badge-success">INVOICE_PAID</span>
                                                                @break

                                                                @default
                                                                    <span class="badge badge-secondary">{{ $data->status }}</span>
                                                            @endswitch
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($data->epp)
                                                            {{ $data->epp }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($data->status == 'PROCESS_TRANSFER')
                                                            <div class="d-flex">
                                                                <button class="btn btn-primary"
                                                                    onclick="showTransferModal('{{ $data->domain }}')">
                                                                    Done & Start Rent
                                                                </button>
                                                            </div>
                                                        @endif
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
                <button class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/addonsmodule?module=selldomain') }}'">
                    <i class="fas fa-arrow-left"></i> Back To Previous Page 
                </button>
            </div>
        </div>
    </div>

    <!-- Transfer Confirmation Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">Confirm Transfer Completion</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="transferForm" method="POST" action="{{ route('addons.selldomain.action') }}">
                    @csrf
                    <input type="hidden" name="action" value="rent_process_transfer">
                    <input type="hidden" name="domain" id="transferDomain">
                    <div class="modal-body">
                        Are you sure you want to complete the transfer and start renting the domain?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Loader -->
    <div class="modal fade" id="modal_loader_selldomain" tabindex="-1" role="dialog" aria-labelledby="modalLoaderLabel" inert>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body d-flex justify-content-center align-items-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <h5 class="ml-3 mb-0" id="content-modal">Fetching data, please wait...</h5>
                </div>
            </div>
        </div>
    </div>
    <!-- Loader -->
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <script src="{{ Theme::asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ Theme::asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
    <script src="{{ Theme::asset('js/notify.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dtable').DataTable({
                responsive: true,
                autoWidth: false
            });

            $('#searchFilterForm').on('submit', function(e) {
                e.preventDefault();
                $('#modal_loader_selldomain').modal('show');
                
                let data = $(this).serialize();
                //console.log(data);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'GET',
                    data: data,
                    success: function(response) {
                        //console.log(response);
                        updateDataTable(response);
                    },
                    error: function(xhr) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Error fetching data'
                        });
                        console.error('Error fetching data:', xhr);
                    },
                    complete: function() {
                        $('#modal_loader_selldomain').modal('hide');
                    }
                });
            });
    
            $('#clearFormButton').on('click', function() {
                $('#searchFilterForm')[0].reset();
            });
        });

        // Initialize Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        function showTransferModal(domain) {
            $('#transferDomain').val(domain);
            $('#transferModal').modal('show');
        }

        function selesaiTransfer(domain) {
            if (confirm('Apakah anda yakin?')) {
                $.post('{{ route('addons.selldomain.action') }}', {
                        action: 'rent_process_transfer',
                        domain: domain,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function() {
                        location.reload();
                    });
            }
        }

        function updateDataTable(data) {
            var table = $('#dtable').DataTable();
            table.destroy();
            $('#dtable tbody').empty();

            data.forEach(function(domain) {
                $('#dtable tbody').append(`
                    <tr>
                        <td>${domain.domain}</td>
                        <td>${domain.price}</td>
                        <td>${domain.clientid}</td>
                        <td>${domain.status}</td>
                        <td>${domain.epp ? domain.epp : '-'}</td>
                        <td>
                            ${domain.status === 'PROCESS_TRANSFER' ? `
                                <div class="d-flex">
                                    <button class="btn btn-primary" onclick="showTransferModal('${domain.domain}')">
                                        Done & Start Rent
                                    </button>
                                </div>
                            ` : ''}
                        </td>
                    </tr>
                `);
            });

            table = $('#dtable').DataTable({
                responsive: true,
                autoWidth: false
            });
        }
    </script>
@endsection