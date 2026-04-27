@extends('layouts.basecbms')

@section('title')
    <title>CBMS Auto - PrivateNS Registrar - Client Documents</title>
@endsection

@section('styles')
    {{-- <link rel="stylesheet" href="{{ asset('vendor/privatensregistrar/css/privatensregistrar.css') }}"> --}}
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
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="card shadow-lg border-0 mt-5 mb-4">
                    <div class="card-body position-relative">
                        <a href="{{ url()->previous() }}" class="text-decoration-none text-dark position-absolute"
                            style="left: 10px; top: 50%; transform: translateY(-50%);">
                            <i class="fas fa-arrow-left ml-4"></i>
                        </a>
                        <h3 class="text-center m-0">Detail Client Documents</h3>
                    </div>
                </div>

                <div class="card shadow-lg border-0">
                    <div class="card-body">
                        <div class="row justify-content-center">
                            @forelse ($documents as $document)
                                <div class="col-md-3 col-sm-6 col-12 mb-4 mt-4 text-center">
                                    <a target="_blank"
                                       href="{{ 'https://' . $_SERVER['SERVER_NAME'] . '/Files/' . $document->file }}">
                                       <img src="{{ 'https://' . $_SERVER['SERVER_NAME'] . '/Files/' . $document->file }}"
                                            class="img document-image" alt="Document Thumbnail">
                                    </a>

                                    <p class="mt-4 mb-1 font-weight-bold">Type: {{ $document->type }}</p>
                                    <p class="text-muted" style="font-size: 0.9rem;">Name: {{ $document->file }}</p>
                                </div>
                            @empty
                                <div class="col-12 text-center">
                                    <p class="text-muted">- No documents available for this client -</p>
                                    <a href="{{ url()->previous() }}" class="btn btn-secondary mt-3">Back</a>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection