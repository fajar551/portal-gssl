@extends('layouts.basecbms')

@section('title')
    <title>CBMS Auto - PrivateNS Registrar</title>
@endsection

@section('styles')
    {{-- <link rel="stylesheet" href="{{ asset('vendor/privatensregistrar/css/privatensregistrar.css') }}"> --}}
    <link rel="stylesheet" href="{{ Theme::asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('content')

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
                    <div class="col-md-12 mb-4">
                        <h2 class="mb-0">PrivateNS Registrar</h2>
                        <small class="text-muted">By CBMS</small>
                    </div>
                   
                    <div class="card col-md-6 ">
                      <div class="card-header">OpenProvider Credentials Test</div>
                       <div class="card-body">
                          @if($credentials)
                              <p><strong>Username:</strong> {{ $credentials->username }}</p>
                              <p><strong>Password:</strong> {{ $credentials->password }}</p>
                          @else
                              <p>No credentials found</p>
                          @endif
                      </div>
                  </div>

                  <div class="card col-md-6">
                    <div class="card-header">OpenProvider Credentials Test</div>
                     <div class="card-body">
                        <div class="mt-4">
                            <h5>Debug Data:</h5>
                            <pre>{{ print_r($credentials, true) }}</pre>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sync TLD -->
@endsection

@section('scripts')
    
@endsection
