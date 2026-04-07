@extends('layouts.clientbase')

@section('page-title')
   Two-Factor Authentication (2FA)
@endsection

@section('content')
<div class="container-fluid" style="margin-top: 100px;">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="ml-4">Two-Factor Authentication (2FA)</h4>
                </div>
                
                <div class="card-body">
                    @if(session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('2fa.verify.post') }}">
    @csrf
    <div class="alert alert-info mb-4">
        Two-factor authentication (2FA) saat ini aktif
    </div>

    <div class="form-group">
        <label>Masukkan 6-digit kode dari Google Authenticator</label>
        <input type="text" 
                                   name="code" 
                                   id="code"
                                   class="form-control @error('code') is-invalid @enderror" 
                                   value="{{ old('code') }}"
                                   required 
                                   autofocus
                                   autocomplete="off"
                                   pattern="[0-9]*"
                                   inputmode="numeric"
                                   minlength="6"
                                   maxlength="6">

        @error('code')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check"></i> Verifikasi
        </button>
        
        <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('home') }}'">
            <i class="fas fa-times"></i> Batal
        </button>
    </div>
</form>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-question-circle"></i>
                            Jika Anda kehilangan akses ke aplikasi authenticator, silakan hubungi support.
                        </small>
                    </div>
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
.btn-secondary {
    background-color: #ff7043;
    border: none;
    color: white;
}
.alert {
    border-radius: 4px;
    padding: 12px 20px;
}
.alert-info {
    background-color: #e3f2fd;
    border-color: #e3f2fd;
    color: #0d47a1;
}
.text-muted {
    color: #666 !important;
}
.mb-4 {
    margin-bottom: 1.5rem;
}
</style>
@endpush