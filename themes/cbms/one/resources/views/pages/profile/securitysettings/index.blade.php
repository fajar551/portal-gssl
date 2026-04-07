@extends('layouts.clientbase')

@section('title')
   Two-Factor Authentication (2FA)
@endsection

@section('page-title')
   Two-Factor Authentication (2FA)
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Two-Factor Authentication (2FA)</h4>
                
                <div class="row">
                    <div class="col-lg-12">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('backupCode'))
                            <div class="alert alert-success">
                                <strong>Two-Factor Authentication (2FA) sudah Aktif</strong>
                                <p>Kode Backup:</p>
                                <pre>{{ session('backupCode') }}</pre>
                                <p>Simpan dan catat Backup Code ini dengan baik.</p>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <p>Two-factor authentication (2FA) saat ini <strong>{{ $twoFactorAuthEnabled ? 'aktif' : 'tidak aktif' }}</strong></p>

                        <div class="alert alert-warning">
                            Kami sangat menyarankan anda untuk mengaktifkan 2FA untuk menambah keamanan Akun Anda
                        </div>

                        <div class="mb-4">
                            <p>Two-Factor Authentication adds an extra layer of protection to your account. Once enabled, you'll need to enter both your password and a security code from your authenticator app when logging in.</p>
                        </div>

                           <div class="text-center">
                                @if(!$twoFactorAuthEnabled)
                                    <a href="#" class="btn btn-success" data-toggle="modal" data-target="#setupModal">
                                        Klik disini untuk Enable
                                    </a>
                                @else
                                    {{--<form action="{{ route('2fa.disable') }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            Klik disini untuk Disable
                                        </button>
                                    </form>--}}
                                    <button class="btn btn-danger" data-toggle="modal" data-target="#disable2FAModal">
                                        Klik disini untuk Disable
                                    </button>
                                @endif
                            </div>

                        <hr class="my-4">

                        <!-- Connected Accounts Section -->
                        <h5 class="mb-3">Akun yang terhubung</h5>
                        <p class="text-muted">Hubungkan akun anda untuk mempermudah login. Kami hanya menggunakan informasi ini untuk memverifikasi akun dan tidak akan melakukan posting tanpa ijin anda.</p>

                        @if(session('error_connection'))
                            <div class="alert alert-danger">
                                Error: Saat ini sistem tidak dapat terhubung ke akun anda. Anda tetap dapat daftar/login dengan form yang tersedia.
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Layanan</th>
                                        <th>Nama</th>
                                        <th>Alamat Email</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Google</td>
                                        <td>{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</td>
                                        <td>{{ Auth::user()->email }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">Lepaskan akun</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


      <!-- Modal -->
   <div class="modal fade" id="disable2FAModal" tabindex="-1" role="dialog" aria-labelledby="disable2FAModalLabel" aria-hidden="true">
       <div class="modal-dialog" role="document">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title" id="disable2FAModalLabel">Nonaktifkan Two-Factor Authentication (2FA)</h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                       <span aria-hidden="true">&times;</span>
                   </button>
               </div>
               <div class="modal-body">
                   <div id="disable2FAContent">
                       <form id="disable2FAForm" action="{{ route('2fa.disable') }}" method="POST">
                           @csrf
                           <div class="form-group">
                               <label for="password">Masukan Password:</label>
                               <input type="password" class="form-control" id="password" name="password" required>
                           </div>
                       </form>
                   </div>
                   <div id="successMessage" style="display: none;">
                       Two-Factor Authentication telah berhasil dinonaktifkan!
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                   <button type="submit" class="btn btn-danger" form="disable2FAForm">Nonaktifkan Two-Factor Authentication (2FA)</button>
               </div>
           </div>
       </div>
   </div>

<!-- Setup Modal -->
<div class="modal fade" id="setupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Setup Two-Factor Authentication</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="setup-steps">
                    <!-- Step 1 -->
                    <div class="step mb-4">
                        <h5>1. Install Google Authenticator</h5>
                        <p>Download dan install Google Authenticator di smartphone Anda:</p>
                        <div class="app-links text-center">
                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" 
                               target="_blank" class="btn btn-outline-primary me-2">
                                Android - Google Play
                            </a>
                            <a href="https://apps.apple.com/us/app/google-authenticator/id388497605" 
                               target="_blank" class="btn btn-outline-primary">
                                iPhone - App Store
                            </a>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="step mb-4">
                        <h5>2. Scan QR Code</h5>
                        <p class="text-center">Buka Google Authenticator dan scan QR code ini:</p>
                        <div class="qr-container">

                        </div>
                        <div class="manual-key">
                            
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="step">
                        <h5>3. Verifikasi Setup</h5>
                        <p class="text-center">Masukkan 6-digit kode dari Google Authenticator:</p>
                        <form method="POST" action="{{ route('2fa.enable') }}" class="verification-form">
                            @csrf
                            <div class="form-group">
                                <div class="col-md-6 offset-md-3">
                                    <input type="text" name="code" 
                                           class="form-control text-center" 
                                           placeholder="000000" 
                                           maxlength="6" 
                                           required
                                           style="font-size: 1.2em; letter-spacing: 3px;">
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary">
                                    Verifikasi dan Aktifkan 2FA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Backup Code Display -->
                   @if (session('backupCode'))
                       <div class="alert alert-success mt-4">
                           <strong>Two-Factor Authentication (2FA) sudah Aktif</strong>
                           <p>Kode Backup:</p>
                           <pre>{{ session('backupCode') }}</pre>
                           <p>Simpan dan catat Backup Code ini dengan baik.</p>
                       </div>
                   @endif
            </div>
        </div>
    </div>
</div>

<style>
.step {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.qr-code {
    max-width: 200px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.app-links {
    margin: 20px 0;
}

.app-links .btn {
    margin: 0 5px;
}

.verification-form {
    max-width: 400px;
    margin: 0 auto;
}

.secret-key {
    font-family: monospace;
    background: #fff !important;
    border: 1px solid #ddd;
}

.qr-container img {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    width: 200px !important;
    height: 200px !important;
    margin: 0 auto;
}
</style>
@endsection

@section('scripts')
<script>
// Fungsi validasi URL
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

$('#setupModal').on('show.bs.modal', function (e) {
    console.log('Modal opening, requesting QR code...');
    
    $.ajax({
        url: '{{ route("2fa.setup") }}',
        method: 'GET',
        success: function(response) {
            console.log('Response received:', response);
            
            if(response.qrCodeUrl) {
                // Gunakan QR Server API sebagai alternatif
                var qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?' +
                    'size=200x200' +
                    '&data=' + encodeURIComponent(response.qrCodeUrl);
                
                console.log('Generated QR URL:', qrImageUrl);
                
                // Tampilkan QR code
                $('.qr-container').html(`
                    <div class="text-center">
                        <img src="${qrImageUrl}" 
                             alt="QR Code" 
                             class="qr-code"
                             style="background: white; padding: 15px; border-radius: 8px; margin: 20px auto;">
                    </div>
                `);
                
                // Tampilkan secret key
                $('.manual-key').html(`
                    <div class="text-center mt-3">
                        <p class="text-muted mb-1">Atau masukkan kode ini secara manual:</p>
                        <code class="secret-key" style="background: #f8f9fa; padding: 8px 12px; border-radius: 4px; font-size: 1.1em;">
                            ${response.secret}
                        </code>
                    </div>
                `);

                // Monitor image loading
                $('.qr-code').on('load', function() {
                    console.log('QR code image loaded successfully');
                }).on('error', function() {
                    console.log('QR code image failed to load');
                    $(this).after('<p class="text-danger">Failed to load QR code. Please try refreshing.</p>');
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            $('.qr-container').html('<div class="alert alert-danger">Error generating QR code</div>');
        }
    });
});

// Debug modal events
$('#setupModal').on('shown.bs.modal', function () {
    console.log('Modal fully shown');
    console.log('QR container contents:', $('.qr-container').html());
});

@if(session('show_setup_modal'))
    $('#setupModal').modal('show');
@endif

// Clear QR code when modal is closed
$('#setupModal').on('hidden.bs.modal', function () {
    $('.qr-container').empty();
    $('.manual-key').empty();
});

// Handle QR code image load errors
function handleQRError(img) {
    console.error('QR code image failed to load');
    $(img).parent().html('<div class="alert alert-danger">Failed to load QR code image</div>');
}


 document.getElementById('disable2FAForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Perform AJAX request
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide form and button, show success message
                document.getElementById('disable2FAContent').style.display = 'none';
                document.querySelector('.modal-footer button[type="submit"]').style.display = 'none';
                document.getElementById('successMessage').style.display = 'block';
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Add event listener to the "Tutup" button
    document.querySelector('.modal-footer .btn-secondary').addEventListener('click', function() {
        window.location.reload();
    });


</script>
@endsection