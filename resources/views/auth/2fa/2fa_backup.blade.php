@extends('layouts.clientbase')

@section('page-title')
   Login with Backup Code
@endsection

@section('content')
<div class="container-fluid" style="margin-top: 100px;">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="ml-4">Login using Backup Code</h4>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('2fa.backup') }}">
                        @csrf
                        <div class="form-group">
                            <label for="backup_code">Backup Code</label>
                            <input type="text" 
                                   name="backup_code" 
                                   id="backup_code"
                                   class="form-control @error('backup_code') is-invalid @enderror" 
                                   required 
                                   autofocus
                                   autocomplete="off">

                            @error('backup_code')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    border: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.card-header {
    background: none;
    border-bottom: none;
    padding: 1.5rem 1.5rem 0;
}
.card-header h4 {
    margin: 0;
    color: #333;
}
.card-body {
    padding: 1.5rem;
}
.form-control {
    height: 45px;
    border-radius: 4px;
}
.btn {
    padding: 8px 20px;
    border-radius: 25px;
}
.btn-primary {
    background-color: #2196f3;
    border: none;
}
.alert {
    border-radius: 4px;
    padding: 12px 20px;
}
.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
.text-muted {
    color: #666 !important;
}
.mb-4 {
    margin-bottom: 1.5rem;
}
</style>
@endpush