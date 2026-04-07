@extends('layouts.clientbase')

@section('tab-title')
    My Services
@endsection

<style>
    .custom-dropdown {
        position: relative;
    }
    .dropdown-content {
        position: absolute;
        top: -100%;
        left: 0;
        background-color: #f9f9f9;
        min-width: 100%;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 9999;
    }
    .dropdown-item {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }
    .dropdown-item:hover {
        background-color: #f1f1f1;
    }
</style>

@section('content')
    <div class="page-content mb-5" id="my-service">
        <div class="container-fluid">
            {{-- Alert untuk pesan error --}}
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card p-4 mb-4">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <h1 class="font-weight-bold">Welcome, {{ ucfirst(session('user.firstname', $user->firstname ))}}</h1>
                                    <span>On this page you can find out what services you have and details about our products</span>
                                </div>
                                <button class="btn btn-success px-3">Learn More</button>
                            </div>
                            <div class="col-md-5 text-center">
                                <img src="{{ "https://my.hostingnvme.id/assets/images/relabs/service3.png" }}" class="img-fluid" alt="service.png">
                            </div>
                        </div>
                    </div>
                    <!-- Services Table -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="font-weight-bold mb-3">{{ __('client.yourservices') }}</h5>
                            <div class="mb-3">
                                <select class="form-control d-inline w-auto" id="sort-time">
                                    <option value="all">Semua Due Date</option>
                                    <option value="asc">Due Date (Terdekat)</option>
                                    <option value="desc">Due Date (Terjauh)</option>
                                </select>
                                <select class="form-control d-inline w-auto ml-2" id="filter-status">
                                    <option value="All">Semua Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <select class="form-control d-inline w-auto ml-2" id="filter-year">
                                    <option value="All">Semua Tahun</option>
                                    @for ($year = 2021; $year <= date('Y') + 1; $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                                <button class="btn btn-success ml-2" onclick="applyFilters()">Filter</button>
                            </div>
                            <div class="table-responsive">
                                <table id="services-table" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Service Name</th>
                                            <th>Domain</th>
                                            <th>{{ __('client.clientareahostingnextduedate') }}</th>
                                            <th>{{ __('client.recurringamount') }}</th>
                                            <th>{{ __('client.status') }}</th>
                                            <th>{{ __('client.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!$serviceProd->isEmpty())
                                            @foreach ($serviceProd as $index => $prod)
                                                <tr class="service-item" data-nextduedate="{{ $prod->nextduedate }}">
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $prod->name }}</td>
                                                    <td>{{ $prod->domain ?? '-' }}</td>
                                                    <td data-order="{{ $prod->nextduedate }}">
                                                        {{ \Carbon\Carbon::parse($prod->nextduedate)->translatedFormat('j F Y') }}
                                                    </td>
                                                    <td>{{ \App\Helpers\Format::price($prod->amount) }}</td>
                                                    <td>
                                                        @switch($prod->domainstatus)
                                                            @case('Terminated')
                                                                <span class="badge badge-danger">{{ $prod->domainstatus }}</span>
                                                            @break

                                                            @case('Pending')
                                                                <span class="badge badge-warning">{{ $prod->domainstatus }}</span>
                                                            @break

                                                            @case('Cancelled')
                                                                <span class="badge badge-secondary">{{ $prod->domainstatus }}</span>
                                                            @break

                                                            @case('Suspended')
                                                                <span class="badge badge-info">{{ $prod->domainstatus }}</span>
                                                            @break

                                                            @default
                                                                <span class="badge badge-success">{{ $prod->domainstatus }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-success" href="{{ url('services/servicedetails/' . $prod->id) }}">{{ __('client.clientareaviewdetails') }}</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">{!! __('client.clientHomePanelsactiveProductsServicesNone') !!}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        const table = $('#services-table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: 6 }
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                }
            ],
            drawCallback: function(settings) {
                const api = this.api();
                const order = api.order();
                if (order[0][0] === 0 && order[0][1] === 'asc') {
                    api.rows({ page: 'current' }).every(function(rowIdx, tableLoop, rowLoop) {
                        const pageInfo = api.page.info();
                        $('td:eq(0)', this.node()).html(pageInfo.start + rowLoop + 1);
                    });
                }
            }
        });

        window.applyFilters = function() {
            const order = document.getElementById('sort-time').value;
            const status = document.getElementById('filter-status').value;
            const selectedYear = document.getElementById('filter-year').value;

            // Clear previous filters
            table.search('').columns().search('');

            // Apply status filter
            if (status !== 'All') {
                table.column(5).search(status);
            }

            // Apply year filter
            if (selectedYear !== 'All') {
                table.column(3).search(selectedYear);
            }

            // Get current date and calculate date ranges
            const currentDate = new Date();
            const oneMonthLater = new Date(currentDate);
            oneMonthLater.setMonth(currentDate.getMonth() + 1);

            const twoMonthsLater = new Date(currentDate);
            twoMonthsLater.setMonth(currentDate.getMonth() + 2);

            const oneYearLater = new Date(currentDate);
            oneYearLater.setFullYear(currentDate.getFullYear() + 1);

            // Custom filter for due date
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const nextDueDate = new Date($(table.row(dataIndex).node()).data('nextduedate'));
                    if (order === 'asc') {
                        return nextDueDate >= currentDate && nextDueDate <= oneMonthLater;
                    } else if (order === 'desc') {
                        return nextDueDate >= twoMonthsLater && nextDueDate <= oneYearLater;
                    }
                    return true;
                }
            );

            table.draw();

            $.fn.dataTable.ext.search.pop();
        };

        document.querySelector('button[onclick="applyFilters()"]').addEventListener('click', applyFilters);
    });
</script>