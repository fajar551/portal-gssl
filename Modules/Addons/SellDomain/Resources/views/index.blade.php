@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - Penjualan Domain</title>
@endsection

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mb-2">
                    <h2 class="mb-0">Penjualan Domain</h2>
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
                {{-- Rent Button --}}
                <div class="col-md-12">
                    <form action="{{ url('/admin/addonsmodule') }}" method="GET" style="display:inline;">
                        <input type="hidden" name="module" value="selldomain">
                        <input type="hidden" name="page" value="rent">
                        <button type="submit" class="btn btn-secondary">
                            Rent Page
                        </button>
                    </form>
                </div>
                {{-- Search and Filter --}}
                <div class="col-md-12 mt-3">
                    <div class="accordion" id="accordionExample">
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

                                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
                                            <div class="card-body">
                                                <form id="searchFilterForm" action="{{ route('addons.selldomain.index') }}" method="GET">
                                                    <input type="hidden" name="module" value="selldomain" />
                                                    <input type="hidden" name="isFilter" value="true" />
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select name="status" class="form-control">
                                                            <option value="">Pilih</option>
                                                            <option value="NEED_VERIFY">NEED_VERIFY</option>
                                                            <option value="VERIFIED">ON_SELLING</option>
                                                            <option value="INVOICE_PAID">INVOICE_PAID</option>
                                                            <option value="PROCESS_TRANSFER">PROCESS_TRANSFER</option>
                                                            <option value="SETTLED">SETTLED</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Domain</label>
                                                        <input type="text" name="domain" class="form-control" placeholder="example.com">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Open Lelang</label><br />
                                                        <div class="row">
                                                            <div class="col-md-6">Dari tanggal :<input class="form-control my-1" name="open_lelang[]" type="date" id="start_open"></div>
                                                            <div class="col-md-6">Ke tanggal : <input class="form-control my-1" name="open_lelang[]" type="date" id="end_open"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Close Lelang</label><br />
                                                        <div class="row">
                                                            <div class="col-md-6">Dari tanggal :<input class="form-control my-1" name="close_lelang[]" type="date" id="start_close"></div>
                                                            <div class="col-md-6">Ke tanggal : <input class="form-control my-1" name="close_lelang[]" type="date" id="end_close"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Tipe Harga</label>
                                                        <select name="type" class="form-control">
                                                            <option value="">Pilih</option>
                                                            <option value="FIX_PRICE">FIXED</option>
                                                            <option value="AUCTION_PRICE">LELANG</option>
                                                        </select>
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
                                            <th scope="col">Client</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">EPP</th>
                                            <th scope="col">OPEN_LELANG</th>
                                            <th scope="col">CLOSE_LELANG</th>
                                            <th scope="col">Invoice Paid</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($domains as $data)
                                            <tr>
                                                <td>{{ $data->domain }}</td>
                                                <td><a href="{{ url('admin/clients/clientssummary?userid=' . $data->clientid) }}" target="_blank">{{ $data->clientid }}</a></td>
                                                <td>
                                                    @php
                                                        $status = $data->status == 'VERIFIED' ? 'ON_SELLING' : $data->status;
                                                        $badgeClass = '';

                                                        switch ($status) {
                                                            case 'PROCESS_TRANSFER':
                                                                $badgeClass = 'badge-info';
                                                                break;
                                                            case 'INVOICE_PAID':
                                                                $badgeClass = 'badge-warning';
                                                                break;
                                                            case 'REJECTED':
                                                                $badgeClass = 'badge-danger';
                                                                break;
                                                            case 'ON_SELLING':
                                                                $badgeClass = 'badge-success';
                                                                break;
                                                            case 'NEED_VERIFY':
                                                                $badgeClass = 'badge-secondary';
                                                                break;
                                                            case 'SETTLED':
                                                                $badgeClass = 'badge-primary';
                                                                break;
                                                            default:
                                                                $badgeClass = 'badge-light';
                                                                break;
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ $status }}
                                                    </span>
                                                    @if($status === 'REJECTED' && !empty($data->notes))
                                                        <span>[Notes: {{ $data->notes }}]</span>
                                                    @endif
                                                </td>
                                                <td>{{ $data->price }}</td>
                                                <td>
                                                    @if($data->epp)
                                                        <button onclick="lihatEPP(this,'{{ $data->epp }}')" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Lihat EPP"><i class="fas fa-eye"></i></button>
                                                        <span style="display:none">{{ $data->epp }}</span>
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($data->open_date)
                                                        {{ $data->open_date }}
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($data->close_date)
                                                        {{ $data->close_date }}
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                                <td>{!! $data->invoiceid ? '<a target="_blank" href="' . route('admin.pages.billing.invoices.edit', ['id' => $data->invoiceid]) . '"> #' . $data->invoiceid : '' !!}</td>
                                                <td>{{ $data->created_at }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap justify-content-start">
                                                        @if ($data->status == 'INVOICE_PAID')
                                                            <form id="transferDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                                @csrf
                                                                <input type="hidden" name="action" value="proses_transfer">
                                                                <input type="hidden" name="domain" value="{{ $data->domain }}">
                                                                <input type="hidden" id="clientid" name="clientid" value="{{ $data->clientid }}">
                                                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#transferDomainModal" data-placement="top" title="Proses transfer">
                                                                    <i class="fas fa-exchange-alt"></i>
                                                                </button>
                                                            </form>
                                                            <form id="eppSalahForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                                @csrf
                                                                <input type="hidden" name="action" value="notif_epp_salah">
                                                                <input type="hidden" name="domain" value="{{ $data->domain }}">
                                                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#eppSalahModal" data-placement="top" title="Info EPP salah">
                                                                    <i class="fas fa-info"></i>
                                                                </button>
                                                            </form>
                                                        @elseif ($data->status == 'PROCESS_TRANSFER')
                                                            <form id="cairkanDanaForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                                @csrf
                                                                <input type="hidden" name="action" value="proses_dicairkan">
                                                                <input type="hidden" id="clientid" name="clientid" value="{{ $data->clientid }}">
                                                                <input type="hidden" id="domain" name="domain" value="{{ $data->domain }}">
                                                                <input type="hidden" id="invoiceid" name="invoiceid" value="{{ $data->invoiceid }}">
                                                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#cairkanDanaModal" onclick="setCairkanDana(this)" data-clientid="{{ $data->clientid }}" data-domain="{{ $data->domain }}" data-invoiceid="{{ $data->invoiceid }}" data-placement="top" title="Cairkan Dana">
                                                                    <i class="fas fa-money-bill"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($data->status == 'VERIFIED')
                                                            <form id="updateInvoicePaidForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                                @csrf
                                                                <input type="hidden" name="action" value="update_invoice_paid">
                                                                <input type="hidden" name="domain" value="{{ $data->domain }}">
                                                                <input type="hidden" id="form-invoiceid" name="invoiceid">
                                                                <button type="button" class="btn btn-success mr-1 mb-1" data-toggle="modal" data-target="#updateInvoiceModal" data-placement="top" title="Update Invoice Paid">
                                                                    <i class="fas fa-pencil-alt"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($data->status !== 'NEED_VERIFY')
                                                            @if ($data->type == 'AUCTION_PRICE')
                                                                @if ($data->status !== 'REJECTED')
                                                                    <button class="btn btn-info mr-1 mb-1" onclick="viewAllInvoice('{{ $data->domain }}')" data-toggle="tooltip" data-placement="top" title="Lihat semua invoice">
                                                                        <i class="fas fa-file-alt"></i>
                                                                    </button>
                                                                @endif  
                                                            @endif
                                                        @endif

                                                        @if (in_array($data->status, ['NEED_VERIFY', 'ON_SELLING']))
                                                            <form id="rejectDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                                @csrf
                                                                <input type="hidden" name="action" value="reject_sell_domain">
                                                                <input type="hidden" name="id" value="{{ $data->id }}">
                                                                <button type="button" class="btn btn-danger mr-1 mb-1" data-toggle="modal" data-target="#rejectDomainModal" data-placement="top" title="Reject domain">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form id="parkDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                            @csrf
                                                            <input type="hidden" name="action" value="park_domain">
                                                            <input type="hidden" name="domain" value="{{ $data->domain }}">
                                                            <button type="button" class="btn btn-secondary mr-1 mb-1" data-toggle="modal" data-target="#parkDomainModal" data-placement="top" title="Park Domain">
                                                                <i class="fa fa-globe"></i>
                                                            </button>
                                                        </form>

                                                        <form id="deleteDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                                            @csrf
                                                            <input type="hidden" name="action" value="delete_sell_domain">
                                                            <input type="hidden" name="domain" value="{{ $data->domain }}">
                                                            <button type="button" class="btn btn-danger mr-1 mb-1" data-toggle="modal" data-target="#deleteDomainModal" data-placement="top" title="Delete Domain">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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

    <!-- Example Modal -->
    {{-- <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Pencairan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="databank">Bank:</label>
                        <input id="databank" class="form-control" value="-" disabled />
                    </div>
                    <div class="form-group">
                        <label for="datarekening">Rekening:</label>
                        <input id="datarekening" class="form-control" value="-" disabled />
                    </div>
                    <div class="form-group">
                        <label for="dataan">Atas Nama:</label>
                        <input id="dataan" class="form-control" value="-" disabled />
                    </div>
                    <div class="mt-3">
                        <small>Penjualan: Rp <span id="penjualan">-</span></small>
                        <small>Biaya Admin <span id="persentase_biaya_admin"></span>: Rp <span id="biaya_admin">-</span></small>
                        <small>Fee Bank: Rp <span id="fee_bank">-</span></small>
                        <small>Total Dana Yang dicairkan: Rp <span id="cair_dana">-</span></small>
                    </div>
                    <hr />
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="image-file">Upload Bukti Transfer</label>
                            <input type="file" class="form-control-file" id="image-file" />
                        </div>
                        <button type="button" id="upload-file" class="btn btn-primary" onclick="uploadFile()">Upload</button>
                    </form>
                    <div class="text-center mt-3">
                        <a id="img_url" href="#">
                            <img id="img_upload" src="" class="img-fluid" style="width: 40%;">
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="sudahCairkan()">Sudah di Cairkan</button>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- View All Invoice Modal -->
    <div class="modal fade" id="viewAllInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">All <span id="domain-invoice"> domain.com</span> Invoices</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row" id="body-invoices">
                        <!-- Invoice content will be dynamically inserted here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Domain Modal -->
    <div class="modal fade" id="rejectDomainModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi reject domain</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="rejectDomainForm">
                        <label>Reason/Alasan</label>
                        <textarea id="notes-textarea" name="notes" class="form-control" placeholder="Alasan reject"></textarea>
                        <input type="hidden" name="id" id="notes_modal">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> 
                    <button type="button" class="btn btn-primary" id="confirmRejectDomain">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Domain Modal -->
    <div class="modal fade" id="deleteDomainModal" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div style="display:flex; justify-content: space-between">
                        <h5 class="modal-title" style="font-weight:bold">Konfirmasi Delete Domain</h5>
                        <button type="button" class="close" data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    Apakah Anda ingin menghapus domain ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDeleteDomain">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Park Domain Modal -->
    <div class="modal fade" id="parkDomainModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div style="display:flex; justify-content: space-between">
                        <h5 class="modal-title" style="font-weight:bold">Konfirmasi Park Domain</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    Apakah Anda ingin memasang domain ini pada landing page sell domain?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmParkDomain">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Invoice Modal -->
    <div class="modal fade" id="updateInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex justify-content-between">
                        <h5 class="modal-title font-weight-bold">Update Invoice Paid</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <label>Invoice ID:</label>
                    <input type="text" class="form-control" id="modal-invoiceid" name="invoiceid" placeholder="Masukkan invoice id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmUpdateInvoicePaid" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EPP Salah Modal -->
    <div class="modal fade" id="eppSalahModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi EPP Salah</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin mengirim notifikasi EPP salah untuk domain ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEppSalah">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Domain Modal -->
    <div class="modal fade" id="transferDomainModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Transfer Domain</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin memproses transfer domain ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmTransferDomain">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cairkan Dana Modal -->
    <div class="modal fade" id="cairkanDanaModal" tabindex="-1" role="dialog" aria-labelledby="cairkanDanaModalLabel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cairkanDanaModalLabel">Pencairan Dana</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="databank">Bank:</label>
                        <input id="databank" class="form-control" value="-" disabled />
                    </div>
                    <div class="form-group">
                        <label for="datarekening">Rekening:</label>
                        <input id="datarekening" class="form-control" value="-" disabled />
                    </div>
                    <div class="form-group">
                        <label for="dataan">Atas Nama:</label>
                        <input id="dataan" class="form-control" value="-" disabled />
                    </div>
                    <div class="mt-3">
                        <small>Penjualan: Rp <span id="penjualan">-</span></small><br>
                        <small>Biaya Admin <span id="persentase_biaya_admin"></span>: Rp <span id="biaya_admin">-</span></small><br>
                        <small>Fee Bank: Rp <span id="fee_bank">-</span></small><br>
                        <small>Total Dana Yang dicairkan: Rp <span id="cair_dana">-</span></small>
                    </div>
                    <hr />
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="image-file">Upload Bukti Transfer</label>
                            <input type="file" class="form-control-file" id="image-file" />
                        </div>
                        <button type="button" id="upload-file" class="btn btn-primary" onclick="uploadFile()">Upload</button>
                    </form>
                    <div class="text-center mt-3">
                        <a id="img_url" href="#" target="_blank" style="display:none;">
                            <img id="img_upload" src="" class="img-fluid" style="display:none;">
                        </a>
                        <a id="file_icon_url" href="#" target="_blank" style="display:none;">
                            <i id="file_icon" class="fas fa-file" style="font-size: 40px;"></i>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="confirmCairkanDana">Sudah di Cairkan</button>
                </div>
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

    <!-- EPP Modal -->
    <div class="modal fade" id="eppModal" tabindex="-1" role="dialog" aria-labelledby="eppModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eppModalLabel">EPP Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="eppContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
            var table = $('#dtable').DataTable();

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

            $('#modal-invoiceid').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            $(document).on('click', '#confirmCairkanDana', function() {
                $('#cairkanDanaForm').submit();
            });

            $(document).on('click', '#confirmEppSalah', function() {
                $('#eppSalahForm').submit();
            });

            $(document).on('click', '#confirmTransferDomain', function() {
                $('#transferDomainForm').submit();
            });

            $(document).on('click', '#confirmUpdateInvoicePaid', function() {
                var invoiceId = $('#modal-invoiceid').val();
                $('#form-invoiceid').val(invoiceId);
                $('#updateInvoicePaidForm').submit();
            });

            $(document).on('click', '#confirmParkDomain', function() {
                $('#parkDomainForm').submit();
            });

            $(document).on('click', '#confirmRejectDomain', function() {
                const form = $('#rejectDomainForm');
                const action = form.find('input[name="action"]').val();
                const id = form.find('input[name="id"]').val();

                console.log(action);
                console.log(id);

                var note_modal = $('#notes-textarea').val();
                $('#notes').val(note_modal);

                console.log(note_modal);

                if (!note_modal) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Alasan tidak boleh kosong'
                    });
                    return;
                }

                if (!id) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Id tidak boleh kosong'
                    });
                    return;
                }

                fetch('{{ route('addons.selldomain.action') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: 'reject_sell_domain',
                        id: id,
                        notes: note_modal
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // $('#dtable').DataTable().row($(`tr:has(td:contains(${id}))`)).remove().draw();
                        updateDataTable(data.data);
                        $('#rejectDomainModal').modal('hide');
                        Toast.fire({
                            icon: 'success',
                            title: data.message
                        });
                    } else {
                        $('#rejectDomainModal').modal('hide');
                        Toast.fire({
                            icon: 'error',
                            title: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#rejectDomainModal').modal('hide');
                    Toast.fire({
                        icon: 'error',
                        title: 'An error occurred'
                    });
                });
            });
            $(document).on('click', '#confirmDeleteDomain', function() {
                $('#deleteDomainForm').submit();
            });

            $('#image-file').on('change', function() {
                let photo = this.files[0];
                if (photo) {
                    //console.log('File selected:', photo);
                } else {
                    //console.log('No file selected');
                }
            });
        });

        function updateDataTable(data) {
            var table = $('#dtable').DataTable();
            table.destroy();
            $('#dtable tbody').empty();

            data.forEach(function(domain) {
                $('#dtable tbody').append(`
                    <tr>
                        <td>${domain.domain}</td>
                        <td><a href="/admin/clients/clientssummary?userid=${domain.clientid}" target="_blank">${domain.clientid}</a></td>
                        <td>
                            @php
                                $status = $data->status == 'VERIFIED' ? 'ON_SELLING' : $data->status;
                                $badgeClass = '';

                                switch ($status) {
                                    case 'PROCESS_TRANSFER':
                                        $badgeClass = 'badge-info';
                                        break;
                                    case 'INVOICE_PAID':
                                        $badgeClass = 'badge-warning';
                                        break;
                                    case 'REJECTED':
                                        $badgeClass = 'badge-danger';
                                        break;
                                    case 'ON_SELLING':
                                        $badgeClass = 'badge-success';
                                        break;
                                    case 'NEED_VERIFY':
                                        $badgeClass = 'badge-secondary';
                                        break;
                                    case 'SETTLED':
                                        $badgeClass = 'badge-primary';
                                        break;
                                    default:
                                        $badgeClass = 'badge-light';
                                        break;
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ $status }}
                            </span>
                            @if($status === 'REJECTED' && !empty($data->notes))
                                <span>[Notes: {{ $data->notes }}]</span>
                            @endif
                        </td>
                        <td>${domain.price}</td>
                        <td>
                            <button onclick="lihatEPP(this,'${domain.epp}')" class="btn btn-info" data-toggle="tooltip" data-placement="top" title="Lihat EPP"><i class="fas fa-eye"></i></button>
                            <span style="display:none">${domain.epp}</span>
                        </td>
                        <td>
                            ${domain.open_date ? `<span>${domain.open_date}</span>` : `<span>-</span>`}
                        </td>
                        <td>
                            ${domain.close_date ? `<span>${domain.close_date}</span>` : `<span>-</span>`}
                        </td>
                        <td>${domain.invoiceid ? `<a target="_blank" href="/admin/pages/billing/invoices/edit/${domain.invoiceid}"> #${domain.invoiceid}</a>` : ''}</td>
                        <td>${domain.created_at}</td>
                        <td>
                            <div class="d-flex flex-wrap justify-content-start">
                                ${domain.status === 'INVOICE_PAID' ? `
                                    <form id="transferDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                        <input type="hidden" name="action" value="proses_transfer">
                                        <input type="hidden" name="domain" value="${domain.domain}">
                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#transferDomainModal" data-placement="top" title="Proses transfer">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </form>
                                    <form id="eppSalahForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                        <input type="hidden" name="action" value="notif_epp_salah">
                                        <input type="hidden" name="domain" value="${domain.domain}">
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#eppSalahModal" data-placement="top" title="Info EPP salah">
                                            <i class="fas fa-info"></i>
                                        </button>
                                    </form>
                                ` : ''}
                                ${domain.status === 'PROCESS_TRANSFER' ? `
                                    <form id="cairkanDanaForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                        <input type="hidden" name="action" value="proses_dicairkan">
                                        <input type="hidden" id="clientid" name="clientid" value="${domain.clientid}">
                                        <input type="hidden" id="domain" name="domain" value="${domain.domain}">
                                        <input type="hidden" id="invoiceid" name="invoiceid" value="${domain.invoiceid}">
                                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#cairkanDanaModal" onclick="setCairkanDana(this)" data-clientid="${domain.clientid}" data-domain="${domain.domain}" data-invoiceid="${domain.invoiceid}" data-placement="top" title="Cairkan Dana">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                    </form>
                                ` : ''}
                                ${domain.status === 'VERIFIED' ? `
                                    <form id="updateInvoicePaidForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                        <input type="hidden" name="action" value="update_invoice_paid">
                                        <input type="hidden" name="domain" value="${domain.domain}">
                                        <input type="hidden" id="form-invoiceid" name="invoiceid">
                                        <button type="button" class="btn btn-success mr-1 mb-1" data-toggle="modal" data-target="#updateInvoiceModal" data-placement="top" title="Update Invoice Paid">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </form>
                                ` : ''}
                                ${domain.status !== 'NEED_VERIFY' && domain.type === 'AUCTION_PRICE' && domain.status !== 'REJECTED' ? `
                                    <button class="btn btn-info mr-1 mb-1" onclick="viewAllInvoice('${domain.domain}')" data-toggle="tooltip" data-placement="top" title="Lihat semua invoice">
                                        <i class="fas fa-file-alt"></i>
                                    </button>
                                ` : ''}
                                ${['NEED_VERIFY', 'ON_SELLING'].includes(domain.status) ? `
                                    <form id="rejectDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                        <input type="hidden" name="action" value="reject_sell_domain">
                                        <input type="hidden" name="id" value="${domain.id}">
                                        <button type="button" class="btn btn-danger mr-1 mb-1" data-toggle="modal" data-target="#rejectDomainModal" data-placement="top" title="Reject domain">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                ` : ''}
                                <form id="parkDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                    <input type="hidden" name="action" value="park_domain">
                                    <input type="hidden" name="domain" value="${domain.domain}">
                                    <button type="button" class="btn btn-secondary mr-1 mb-1" data-toggle="modal" data-target="#parkDomainModal" data-placement="top" title="Park Domain">
                                        <i class="fa fa-globe"></i>
                                    </button>
                                </form>
                                <form id="deleteDomainForm" method="POST" action="{{ route('addons.selldomain.action') }}" class="mr-1 mb-1">
                                    <input type="hidden" name="action" value="delete_sell_domain">
                                    <input type="hidden" name="domain" value="${domain.domain}">
                                    <button type="button" class="btn btn-danger mr-1 mb-1" data-toggle="modal" data-target="#deleteDomainModal" data-placement="top" title="Delete Domain">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                `);
            });

            table = $('#dtable').DataTable();
        }
    
        function lihatEPP(el, epp = '') {
            if (!epp) {
                $('#eppContent').text('EPP belum dikirim oleh penjual');
            } else {
                $('#eppContent').text('Berikut EPP client: ' + epp);
            }
            $('#eppModal').modal('show');
        }

        function transferDomain(domain) {
            if (window.confirm("Apakah anda yakin?")) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'proses_transfer',
                    domain: domain
                })
                .done(function() {
                    location.reload();
                });
            }
        }

        function modalInvoicePaid() {
            $('#updateInvoiceModal').modal('show');
        }

        function updateInvoicePaid(domain) {
            if (window.confirm("Apakah anda yakin?")) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'update_invoice_paid',
                    domain: domain,
                    _token: '{{ csrf_token() }}'
                })
                .done(function() {
                    location.reload();
                });
            }
        }

        function sudahCairkan(domain, clientid) {
            if (!Boolean(domain)) {
                domain = window.current_domain;
            }

            if (!Boolean(clientid)) {
                clientid = window.current_clientid;
            }

            if (window.confirm("Apakah anda yakin?")) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'proses_dicairkan',
                    domain: domain,
                    _token: '{{ csrf_token() }}'
                })
                .done(function() {
                    location.reload();
                });
            }
        }

        function eppSalah(domain) {
            if (window.confirm("Apakah anda yakin?")) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'notif_epp_salah',
                    domain: domain,
                    _token: '{{ csrf_token() }}'
                })
                .done(function() {
                    location.reload();
                });
            }
        }

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

        function setCairkanDana(button) {
            // Retrieve clientid, domain, and invoiceid from the button's data attributes
            let clientid = $(button).data('clientid');
            let domain = $(button).data('domain');
            let invoiceid = $(button).data('invoiceid');

            $('#cairkanDanaModal').modal('show');

            // Check if the image is uploaded
            if (!$('#img_upload').attr('src') && !$('#img_url').attr('href')) {
                Toast.fire({
                    icon: 'error',
                    title: 'Please upload a photo'
                });
                return;
            }

            // Reset the modal fields
            $('#databank, #datarekening, #dataan').val('-');
            $('#cair_dana, #biaya_admin, #penjualan, #persentase_biaya_admin, #fee_bank').text('-');

            $('#modal_loader_selldomain').modal('show');

            // Fetch bank details using the correct clientid
            $.post('{{ route('addons.selldomain.action') }}', {
                action: 'get_bank',
                clientid: clientid,
                _token: '{{ csrf_token() }}'
            })
            .done(function(r) {
                $('#databank').val(r.bank);
                $('#datarekening').val(r.rekening);
                $('#dataan').val(r.atasnama);
                $('#modal_loader_selldomain').modal('hide');
            })
            .fail(function(response) {
                console.log(response);
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to fetch bank details.'
                });
                $('#modal_loader_selldomain').modal('hide');
            });

            // Fetch financial details if invoiceid is available
            if (Boolean(invoiceid)) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'get_dana',
                    invoiceid: invoiceid,
                    clientid: clientid
                })
                .done(function(r) {
                    $('#cair_dana').text(r.data.withdraw);
                    $('#biaya_admin').text(r.data.adminprice);
                    $('#penjualan').text(r.data.amount);
                    $('#persentase_biaya_admin').text(r.data.adminpersen);
                    $('#fee_bank').text(r.data.feebank);
                    $('#modal_loader_selldomain').modal('hide');
                });
            }
        }

        function viewAllInvoice(domain) {
            $('#modal_loader_selldomain').modal('show');
            $('#body-invoices').empty();

            $.post('{{ route('addons.selldomain.action') }}', {
                action: 'list_invoices',
                domain: domain,
                _token: '{{ csrf_token() }}'
            })
            .done(function(datas) {
                if (datas.length === 0) {
                    $('#body-invoices').append('<div class="col-md-12 text-center">Invoice untuk domain ini tidak ditemukan</div>');
                } else {
                    datas.forEach(function(obj) {
                        $('#body-invoices').append('<div class="col-md-3"><a target="_blank" href="/qwadmin/invoices.php?action=edit&id=' + obj.invoice + '"> #' + obj.invoice + ' </a></div>');
                    });
                }
                $('#modal_loader_selldomain').modal('hide');
            });

            $('#viewAllInvoiceModal').modal('show');
        }

        function actionReject() {
            $('#rejectDomainModal').modal('show');
        }

        function deleteDomain() {
            $('#deleteDomainModal').modal('show');
        }

        function parkDomain() {
            $('#parkDomainModal').modal('show');
        }

        function handleFileDisplay(file) {
            const imageExtensions = ['png', 'jpg', 'jpeg'];
            const documentExtensions = ['pdf', 'doc', 'docx'];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if (imageExtensions.includes(fileExtension)) {
                // Display as an image thumbnail
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#img_upload').attr('src', e.target.result).css('width', '40%').show();
                    $('#img_url').attr('href', e.target.result).show();
                    $('#file_icon_url').hide();
                };
                reader.readAsDataURL(file);
            } else if (documentExtensions.includes(fileExtension)) {
                // Display as a file icon
                const fileURL = URL.createObjectURL(file);
                $('#img_upload').hide();
                $('#img_url').hide();
                $('#file_icon_url').attr('href', fileURL).show();
            } else {
                // Unsupported file type
                alert('Unsupported file type. Please upload an image or document.');
                $('#img_upload').hide();
                $('#img_url').hide();
                $('#file_icon_url').hide();
                return false;
            }
            return true;
        }

        function uploadFile() {
            let photo = $('#image-file')[0].files[0];
            if (!photo) {
                alert('Please select a file to upload.');
                return;
            }

            if (!handleFileDisplay(photo)) {
                return;
            }

            let domain = $('#domain').val();
            if (!domain) {
                alert('Domain is not set.');
                return;
            }

            let formData = new FormData($('#uploadForm')[0]);
            formData.append("domain", domain);
            formData.append("_token", '{{ csrf_token() }}');
            formData.append("action", 'upload_bukti');
            formData.append("image", photo);

            // Disable the upload button and change its text
            $('#upload-file').prop('disabled', true).text('Processing...');
            // Disable the "Sudah di Cairkan" button
            $('#confirmCairkanDana').prop('disabled', true);

            $('#modal_loader_selldomain').modal('show');

            $.ajax({
                url: '{{ route('addons.selldomain.action') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(r) {
                    if (r.status == 'success') {
                        $('#img_upload').attr('src', r.data);
                        $('#img_url').attr('href', r.data);
                    } else {
                        alert(r.msg);
                    }
                    $('#modal_loader_selldomain').modal('hide');
                    // Re-enable the buttons and reset the text
                    $('#upload-file').prop('disabled', false).text('Upload');
                    $('#confirmCairkanDana').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                    $('#modal_loader_selldomain').modal('hide');
                    // Re-enable the buttons and reset the text
                    $('#upload-file').prop('disabled', false).text('Upload');
                    $('#confirmCairkanDana').prop('disabled', false);
                }
            });
        }

        function setCairkanDana(button) {
            // Retrieve clientid, domain, and invoiceid from the button's data attributes
            let clientid = $(button).data('clientid');
            let domain = $(button).data('domain');
            let invoiceid = $(button).data('invoiceid');

            $('#cairkanDanaModal').modal('show');

            // Check if the image is uploaded
            if (!$('#img_upload').attr('src') && !$('#img_url').attr('href')) {
                Toast.fire({
                    icon: 'error',
                    title: 'Please upload a photo'
                });
                return;
            }

            // Reset the modal fields
            $('#databank, #datarekening, #dataan').val('-');
            $('#cair_dana, #biaya_admin, #penjualan, #persentase_biaya_admin, #fee_bank').text('-');

            $('#modal_loader_selldomain').modal('show');

            // Fetch bank details using the correct clientid
            $.post('{{ route('addons.selldomain.action') }}', {
                action: 'get_bank',
                clientid: clientid,
                _token: '{{ csrf_token() }}'
            })
            .done(function(r) {
                $('#databank').val(r.bank);
                $('#datarekening').val(r.rekening);
                $('#dataan').val(r.atasnama);
                $('#modal_loader_selldomain').modal('hide');
            })
            .fail(function(response) {
                console.log(response);
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to fetch bank details.'
                });
                $('#modal_loader_selldomain').modal('hide');
            });

            // Fetch financial details if invoiceid is available
            if (Boolean(invoiceid)) {
                $.post('{{ route('addons.selldomain.action') }}', {
                    action: 'get_dana',
                    invoiceid: invoiceid,
                    clientid: clientid
                })
                .done(function(r) {
                    $('#cair_dana').text(r.data.withdraw);
                    $('#biaya_admin').text(r.data.adminprice);
                    $('#penjualan').text(r.data.amount);
                    $('#persentase_biaya_admin').text(r.data.adminpersen);
                    $('#fee_bank').text(r.data.feebank);
                    $('#modal_loader_selldomain').modal('hide');
                });
            }
        }

        setTimeout(function() {
            $('#dtable').dataTable();
        }, 500);
    </script>

@endsection
