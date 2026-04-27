@extends('layouts.basecbms')

@section('title')
    <title>CBMS Auto - PrivateNS Registrar</title>
@endsection

@section('styles')
    {{-- <link rel="stylesheet" href="{{ asset('vendor/privatensregistrar/css/privatensregistrar.css') }}"> --}}
    <link rel="stylesheet" href="{{ Theme::asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('content')
<style>
/* Index Section Start */
@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-100%);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.slide-in-left {
  animation: slideInLeft 0.5s ease-out;
}

.slide-in-right {
  animation: slideInRight 0.5s ease-out;
}

#loadingOverlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-dots {
  font-size: 2rem;
  color: #007bff;
  font-weight: bold;
}

.loading-dots span {
  animation: blink 1.4s infinite both;
}

.loading-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.loading-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes blink {
  0%, 20%, 50%, 80%, 100% {
    opacity: 1;
  }

  40%, 60% {
    opacity: 0;
  }
}

.table-hover tbody tr:hover {
  background-color: #f1f1f1;
}

.table th, .table td {
  padding: 12px;
  vertical-align: middle;
}

.bg-dark {
  background-color: #343a40 !important;
  color: #fff;
}

.btn-primary {
  background-color: #000;
  border: none;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: #333;
}

.rotate-icon {
  animation: rotate 2s linear infinite;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* Index Section End */

/* Modal Approval Start */
#loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 150px;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
  color: #007bff;
}

#loader p {
  font-size: 1.1rem;
  color: #333;
  margin-top: 0.5rem;
  font-weight: 500;
}

#modalApproval {
  overflow: visible;
  z-index: 1050;
}

#modalApproval .modal-dialog {
  max-height: 90vh;
  overflow-y: auto;
}

.img-approval {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 0 auto;
  border: 3px solid #c0c0c0 !important;
  border-radius: 5px !important;
  margin-bottom: 10px !important;
  margin-top: 10px !important;
}

.fade-in-image {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 0 auto;
  cursor: pointer;
  border: 1px solid #ccc;
  padding: 5px;
  transition: transform 0.2s ease-in-out;
}

.fade-in-image.loaded {
  opacity: 1;
}

.fade-in-image:hover {
  transform: scale(1.05);
}

.modal-body img {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 0 auto;
  border: 1px solid #ddd;
  padding: 10px;
}

.no-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  border: 1px dashed #ccc;
  color: #999;
  font-size: 18px;
  background-color: #f8f9fa;
  border-radius: 8px;
  text-align: center;
}

.no-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center !important;
  height: 150px;
  border: 2px dashed #ccc;
  color: #888;
  flex-flow: row wrap;
  font-size: 16px;
  font-weight: bold;
  text-align: center !important;
}

.modal-dialog-centered {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  margin: auto;
}

.modal-content {
  max-width: 500px;
  margin: auto;
  max-height: 90vh;
  overflow-y: auto;
}

/* Image Container */
.document-image {
  border: 3px solid #dcdcdc;
  border-radius: 15px;
  transition: transform 0.3s, box-shadow 0.3s;
  max-height: 250px;
  object-fit: cover;
}

/* Hover Effect for Images */
.document-image:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

/* Flex Container for Responsiveness */
.row.justify-content-center {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

/* Back Button */
.text-decoration-none.text-dark:hover {
  color: #007bff !important;
  text-decoration: underline !important;
}

/* Add Padding for Small Screens */
@media screen and (max-width: 768px) {
  .document-image {
    max-height: 180px;
  }
}

/* Styling for File Info */
.text-muted {
  font-size: 0.9rem;
  color: #6c757d;
}

.font-weight-bold {
  font-weight: 700;
}

/* Modal Approval End */
</style>
    <div id="loadingOverlay">
        <div class="loading-dots">
            <span>.</span>
            <span>.</span>
            <span>.</span>
            <span>.</span>
            <span>.</span>
            <span>.</span>
        </div>
    </div>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <h2 class="mb-0">PrivateNS Registrar</h2>
                        <small class="text-muted">By CBMS</small>
                    </div>
                    <div class="col-md-12">
                        @if (session('alert-message'))
                            <div class="alert alert-{{ session('alert-type') }}" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {!! nl2br(session('alert-message')) !!}
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

                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="btn-group border rounded p-1" style="border-color: #ddd;">
                                        <button id="btnApproval" class="btn btn-light">Need Approval</button>
                                        <button id="btnAllDocuments" class="btn btn-light">All Documents</button>
                                    </div>
                                </div>
                                <div hidden="true">
                                    <button id="btnSync" data-toggle="modal" data-target="#modalsync"
                                        class="btn btn-danger">
                                        <i class="fas fa-sync rotate-icon"></i> TLD Sync
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Approval Section --}}
                    <div id="approvalSection" class="col-md-12 mt-2 table-responsive" style="display: none;">
                        @include('privatensregistrar::partials.approval_table', ['approval' => $approval])
                    </div>

                    {{-- All Documents Section --}}
                    <div id="allDocumentsSection" class="col-md-12 mt-2 table-responsive" style="display: none;">
                        @include('privatensregistrar::partials.all_documents_table', [
                            'all_documents' => $all_documents,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sync TLD -->
    @include('privatensregistrar::partials.modal_approval')
@endsection

@section('scripts')
    <script src="{{ Theme::asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ Theme::asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <script>
        const syncTLDRoute = "{{ route('privatens_registrar.syncTLD') }}";
        const domainDocumentRoute = "{{ route('privatens_registrar.domain_document') }}";
        const processDocumentRoute = "{{ route('privatens_registrar.process_document') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>

    {{-- <script src="{{ asset('vendor/privatensregistrar/js/privatensregistrar.js') }}"></script> --}}
    <script>
      $(document).ready(function () {

$('#approvalSection').show();
$('#allDocumentsSection').hide();

$('#loadingOverlay').fadeOut();

$('#tbl_allDoc').DataTable();
$('#approvalTable').DataTable();

$('#btnApproval').click(function () {
  $('#approvalSection').show();
  $('#allDocumentsSection').hide();
});

$('#btnAllDocuments').click(function () {
  $('#approvalSection').hide();
  $('#allDocumentsSection').show();
});

$('#synctld').click(function () {
  $('#modalsync').modal('hide');
  $('#btnSync').attr('disabled', true);
  $('#btnSync i').addClass('fa-spin');

  $.ajax({
    type: 'POST',
    url: syncTLDRoute,
    dataType: 'json',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    success: function (data) {
      $('#btnSync').removeAttr('disabled');
      $('#btnSync i').removeClass('fa-spin');
      let alertClass = data.error ? 'danger' : 'success';
      let alertIcon = data.error ? 'glyphicon-exclamation-sign' : 'glyphicon-ok-circle';
      $('.msg-alert').html(`<div class="alert alert-${alertClass}">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
                  <span class="glyphicon ${alertIcon} iconleft" aria-hidden="true"></span> ${data.errorMsg}
              </div>`);
    }
  });
  return false;
});

$('.btn-approval').on('click', function () {
  const domain = $(this).data('id');
  $('#domain_name').text(domain);

  $('#loader').show();
  $('#documentContent').hide();

  $.post(domainDocumentRoute, {
    domain: domain,
    _token: csrfToken
  }, function (data) {

    $('#loader').hide();
    $('#documentContent').html(data).show();
  });
});

$('.process-approval, .process-rejection').click(function () {
  const status = $(this).data('status');
  const domain = $('#domain_name').text();
  const note = $('#note-' + $(this).data('id')).val();
  const key = $(this).data('id');

  $.post(processDocumentRoute, {
    domain: domain,
    key: key,
    status: status,
    ket: note,
    _token: csrfToken
  }, function (response) {
    alert(response.msg);
    location.reload();
  });
});

$('.close').click(function () {
  location.reload();
});

$('#approvalSection').show();
$('#allDocumentsSection').hide();
});
    </script>
@endsection
