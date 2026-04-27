@extends('layouts.basecbms')

@section('title')
    <title>CBMS Addons - Change Date Paid Invoice</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h2 class="mb-0">Change Date Paid Invoice Addons</h2>
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

                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                            <h2 class="font-weight-bold mb-0" style="font-size: 24px;">Form Change Date Paid Invoice</h2>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <form id="changeDatePaidInvoiceForm" method="POST" onsubmit="return false;">
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label for="invoiceid">Select Invoice ID</label>
                                            <select name="invoiceid" id="invoiceid" class="form-control" style="width: 100%;">
                                                <option value="">Select Invoice ID</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="status">Status</label>
                                            <select name="status_invoice" id="status_invoice" class="form-control">
                                                <option value="Paid" selected>Paid</option>
                                                {{-- <option value="Unpaid">Unpaid</option> --}}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="datebefore">Date Paid From</label>
                                            <input type="date" name="datebefore" id="datebefore" class="form-control" disabled>
                                        </div>
                                        <div class="form-group col-md-6">

                                            <label for="datechange">Change Date Paid To</label>
                                            <input type="date" name="datechange" id="datechange" class="form-control">
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary" onclick="changeDatePaidInvoice()">Change Date Paid</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- DataTable --}}
                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Detail Invoice</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped wrap" id="dataInvoice">
                                        <thead>
                                            <tr>
                                                <th>Invoice ID</th>
                                                <th>User ID</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Taxed</th>
                                                <th>Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="data">

                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" style="text-align:right">Total:</th>
                                                <th class="total-amount">IDR 0</th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ Theme::asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ Theme::asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
    <script src="{{ Theme::asset('js/notify.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>

    <script>
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

        $(document).ready(function() {
            const table = $('#dataInvoice').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('addons.changedatepaidinvoice.getDataItemInvoiceById') }}',
                    type: 'POST',
                    dataSrc: function(json) {
                        // Use the totalAmount from the server response
                        let totalAmount = json.totalAmount;
                        
                        // Display the total amount in IDR format in the footer
                        $('#dataInvoice tfoot th.total-amount').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalAmount));
                        
                        return json.data;
                    },
                    data: function(d) {
                        d.invoiceid = $('#invoiceid').val();
                    }
                },
                columns: [
                    { data: 'invoiceid', title: 'Invoice ID' },
                    { data: 'userid', title: 'User ID' },
                    { data: 'type', title: 'Type' },
                    { data: 'description', title: 'Description' },
                    { 
                        data: 'amount', 
                        title: 'Amount',
                        render: function(data, type, row) {
                            // Format amount as IDR without decimals
                            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(data);
                        }
                    },
                    { 
                        data: 'taxed', 
                        title: 'Taxed',
                        render: function(data, type, row) {
                            // Render taxed as "Ya" or "Tidak"
                            return data === 1 ? 'Ya' : 'Tidak';
                        }
                    },
                    { data: 'duedate', title: 'Due Date' }
                ],
                columnDefs: [
                    { width: '10%', targets: 0 }, // Set width for Invoice ID
                    { width: '10%', targets: 1 }, // Set width for User ID
                    { width: '30%', targets: 3 }  // Set width for Description
                ],
                order: [
                    [0, 'asc']
                ],
                footerCallback: function(row, data, start, end, display) {
                    // Calculate the total amount for the current page
                    let pageTotal = data.reduce((sum, item) => {
                        // Ensure amount is treated as a float
                        let amount = parseFloat(item.amount);
                        return sum + amount;
                    }, 0);
                    
                    // Update the footer with the page total
                    $(this.api().column(4).footer()).html(
                        new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(pageTotal)
                    );
                }
            });

            $('#invoiceid').on('select2:select', function() {
                table.ajax.reload();
            });

            // Function to apply filters
            window.applyFilters = function() {
                table.ajax.reload();
            };

            // Function to clear filters
            window.clearFilters = function() {
                $('#searchFilterForm')[0].reset();
                table.ajax.reload();
            };

            // Add event listeners to remove invalid state on input
            $('input, select').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });

        });

        function filterBlog() {
            let category = $("#category_filter").val();
            if (!category) {
                alert("Tolong masukkan filter blog.");
                return;
            }

            // Reload the DataTable with the new filter
            $('#dtable-my-blog').DataTable().ajax.reload();
        }
    </script>

    <script>
     $(document).ready(function() {
        $('#invoiceid').select2({
            ajax: {
                url: '{{ route('addons.changedatepaidinvoice.selectInvoiceId') }}',
                type: 'POST',
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;

                    return {
                        results: data.data.map(function(invoice) {
                            return { 
                                id: invoice.id, 
                                text: `ID: ${invoice.id} - UserID: #${invoice.userid} - Date Paid: ${invoice.datepaid}`,
                                datepaid: invoice.datepaid.split(' ')[0]
                            };
                        }),
                        pagination: {
                            more: (params.page * 5) < data.total
                        }
                    };
                },
                cache: true
            },
            placeholder: 'Select Invoice ID',
            minimumInputLength: 1,
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $('#datebefore').val(data.datepaid);
        });
     });
    
        function changeDatePaidInvoice() {
        // Gather form data
        const formData = {
            invoiceid: $('#invoiceid').val(),
            status_invoice: $('#status_invoice').val(),
            datebefore: $('#datebefore').val(),
            datechange: $('#datechange').val(),
            _token: '{{ csrf_token() }}' // Include CSRF token for security
        };

        // Send AJAX request
        $.ajax({
            url: '{{ route('addons.changedatepaidinvoice.processChangePaid') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    // Show success toast
                    Toast.fire({
                        icon: 'success',
                        text: response.message
                    });
                    // Optionally reload the DataTable or perform other actions
                    $('#dataInvoice').DataTable().ajax.reload();
                    location.reload();
                } else {
                    // Show error toast
                    Toast.fire({
                        icon: 'error',
                        text: response.message
                    });
                }
            },

            error: function(xhr, status, error) {
                // Show error toast
                Toast.fire({
                    icon: 'error',
                    text: 'An error occurred. Please try again.'
                });
                console.log(xhr);
            }
        });
    }
    </script>

    <!-- Add a row for displaying the total amount -->
    <div class="mt-3"><strong>Total Amount: </strong><span id="totalAmount">IDR 0</span></div>

@endsection
