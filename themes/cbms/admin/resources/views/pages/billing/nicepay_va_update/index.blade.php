@extends('layouts.basecbms')

@section('title')
    <title>{{ Cfg::getValue('CompanyName') }} — Update Nama VA NICEPAY</title>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-title mb-3">
                            <h4 class="mb-1">NICEPAY — Update nama pelanggan Virtual Account</h4>
                            <p class="text-muted mb-0">
                                Mengirim permintaan <code>updateType = 2</code> ke
                                <code>vacctCustomerUpdate.do</code> sesuai
                                <a href="https://docs.nicepay.co.id/nicepay-api-v2-virtual-account-static#nicepay-virtual-account-update" target="_blank" rel="noopener">dokumentasi NICEPAY</a>.
                            </p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('nicepay_va_update_response'))
                            @php $resp = session('nicepay_va_update_response'); @endphp
                            <div class="alert {{ ($resp['parsed']['resultCd'] ?? '') === '0000' ? 'alert-success' : 'alert-warning' }}">
                                <strong>HTTP {{ $resp['http_status'] }}</strong>
                                @if (is_array($resp['parsed']) && isset($resp['parsed']['resultCd']))
                                    — resultCd: <code>{{ $resp['parsed']['resultCd'] }}</code>
                                    @if (!empty($resp['parsed']['resultMsg']))
                                        — {{ $resp['parsed']['resultMsg'] }}
                                    @endif
                                @endif
                                <details class="mt-2 mb-0">
                                    <summary class="cursor-pointer">Detail request / response</summary>
                                    <pre class="mt-2 mb-0 small bg-light p-2 rounded">{{ json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </div>
                        @endif

                        <div class="card p-3">
                            <form method="post" action="{{ route('admin.pages.billing.nicepay_va_update.update') }}" autocomplete="off">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="vacct_no">Nomor VA</label>
                                    <div class="col-md-6">
                                        <input type="text" name="vacct_no" id="vacct_no" class="form-control"
                                            value="{{ old('vacct_no') }}"
                                            placeholder="Contoh: 7812310820000391" required>
                                        <small class="form-text text-muted">Nomor Virtual Account (hanya angka; tanda pisah opsional).</small>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="customer_id">Customer ID (opsional)</label>
                                    <div class="col-md-6">
                                        <input type="text" name="customer_id" id="customer_id" class="form-control"
                                            value="{{ old('customer_id') }}"
                                            placeholder="Kosongkan = 8 digit terakhir nomor VA">
                                        <small class="form-text text-muted">
                                            API NICEPAY memakai parameter <code>customerId</code> (1–8 digit), bukan nomor VA penuh.
                                            Jika tersimpan selain sufiks VA, isi manual di sini.
                                        </small>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="customer_nm">Nama pelanggan baru</label>
                                    <div class="col-md-6">
                                        <input type="text" name="customer_nm" id="customer_nm" class="form-control"
                                            value="{{ old('customer_nm') }}"
                                            maxlength="30" required>
                                        <small class="form-text text-muted">Maks. 30 karakter sesuai batas parameter <code>customerNm</code>.</small>
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-md-9 offset-md-3">
                                        <button type="submit" class="btn btn-primary">Kirim update ke NICEPAY</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <p class="text-muted small mt-3 mb-0">
                            Pastikan <code>NICEPAY_IMID</code> dan <code>NICEPAY_MERCHANT_KEY</code> di <code>.env</code> sesuai kredensial merchant (merchant token: SHA256(iMid + customerId + merchantKey)).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
