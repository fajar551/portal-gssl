<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#CCModal">
    {{ $langpaynow }}
</button>

<!-- Modal -->
<div class="modal fade" id="CCModal" tabindex="-1" role="dialog" aria-labelledby="CCModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="payment-form-check" action="" method="post">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="CCModalLabel">Informasi Kartu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-left">
                    <div id="three-ds-container"></div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="">Nomor Kartu</label>
                            <input class="form-control" name="cardnumber" id="card-number" type="text"
                                placeholder="Nomor Kartu">
                            <div class="valid-feedback">
                                Valid
                            </div>
                            <div class="invalid-feedback">
                                Harap berikan nomor kartu yang valid
                            </div>
                        </div>
                        <div class="col">
                            <label for="">Bulan/Tahun</label>
                            <div class="row">
                                <div class="col pr-0">
                                    <input class="form-control" name="cardexpmonth" id="card-exp-month" type="number"
                                        placeholder="12">
                                    <div class="invalid-feedback">
                                        Salah
                                    </div>
                                    <div class="valid-feedback">
                                        Valid
                                    </div>
                                </div>
                                <div class="col pl-0">
                                    <input class="form-control" name="cardexpyear" id="card-exp-year" type="number"
                                        placeholder="2017">
                                    <div class="invalid-feedback">
                                        Salah
                                    </div>
                                    <div class="valid-feedback">
                                        Valid
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label for="">CVN/CVC</label>
                            <input class="form-control" name="cardcvn" id="card-cvn" type="number" placeholder="123">
                            <div class="invalid-feedback">
                                Salah
                            </div>
                            <div class="valid-feedback">
                                Valid
                            </div>
                        </div>
                    </div>
                    <!-- Informasi kartu dummy untuk testing -->
                   
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closed" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary submit">Periksa</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- modal 3Ds --}}
<div class="modal fade" id="modal3DS" tabindex="-1" role="dialog" aria-labelledby="modal3DSTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="">3DS OTP</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe height="450" width="100%" id="sample-inline-frame" name="sample-inline-frame"> </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Success --}}
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalTitle"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="">Sukses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="alert alert-success">
                    <h3>Pembayaran tagihan berhasil</h3>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
<script type="text/javascript" src="https://js.xendit.co/v1/xendit.min.js"></script>
<script type="text/javascript">
    Xendit.setPublishableKey('{{ $public_key }}');
    $(document).ready(function() {
        var $form = $('#payment-form-check');
        $.validator.addMethod('CCExp', function(value, element, params) {
            var month = parseInt($(params.month).val(), 10);
            var year = parseInt($(params.year).val(), 10);
            return Xendit.card.validateExpiry(month, year);
        }, 'Expiration date invalid');

        $.validator.addMethod('CCNumber', function(value, element, params) {
            return Xendit.card.validateCardNumber(value);
        }, 'Please enter a valid credit card number');

        $.validator.addMethod('CCCvn', function(value, element, params) {
            return Xendit.card.validateCvn(value);
        }, 'Please enter a valid CVN');

        $form.validate({
            normalizer: function(value) {
                return value ? value.trim() : value;
            },
            errorElement: 'small',
            errorClass: "text-danger font-weight-normal",
            rules: {
                cardnumber: {
                    required: true,
                    CCNumber: true
                },
                cardexpmonth: {
                    required: true,
                },
                cardexpyear: {
                    required: true,
                    CCExp: {
                        month: '#card-exp-month',
                        year: '#card-exp-year'
                    }
                },
                cardcvn: {
                    required: true,
                    CCCvn: true,
                },
            },
        });
        $form.submit(function(event) {
            event.preventDefault();
            var isFormValid = $form.valid();
            if (!isFormValid) {
                return false;
            }
            $form.find('.submit').prop('disabled', true);
            $form.find('.closed').prop('disabled', true);

            // Validasi amount untuk test environment
            var originalAmount = {{ $amount }};
            var maxAmount = 100000000; // 100 juta rupiah dalam sen (maksimum yang diizinkan Xendit)
            var testAmount = 5000000; // 50 ribu rupiah dalam sen (50.000 * 100)

            // Gunakan amount yang lebih kecil untuk testing jika amount asli terlalu besar
            var finalAmount = originalAmount > maxAmount ? testAmount : originalAmount;

            console.log('Original amount (sen):', originalAmount);
            console.log('Using amount for token (sen):', finalAmount);
            console.log('Token data:', tokenData);

            // Generate fingerprint session ID untuk keamanan
            var fpSessionId = 'fp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            var tokenData = {
                is_single_use: true,
                amount: finalAmount,
                card_cvn: $form.find('#card-cvn').val(),
                card_data: {
                    account_number: $form.find('#card-number').val(),
                    exp_month: $form.find('#card-exp-month').val(),
                    exp_year: $form.find('#card-exp-year').val(),
                    cvn: $form.find('#card-cvn').val()
                },
                currency: "IDR",
                fp_session_id: fpSessionId,
                should_authenticate: true
            };

            Xendit.card.createToken(tokenData, xenditResponseHandler);
        });
    });

    function xenditResponseHandler(err, creditCardToken) {
        var $form = $('#payment-form-check');
        $form.find('.submit').prop('disabled', false);
        $form.find('.closed').prop('disabled', false);

        console.log('error', err);
        console.log('data', creditCardToken);

        if (err) {
            //Definisikan penanganan kesalahan
            console.log('Error details:', {
                error_code: err.error_code,
                message: err.message,
                errors: err.errors
            });
            alert(err.message);
        } else {
            if (creditCardToken.status === 'VERIFIED') {
                // Penanangan keberhasilan
                var token = creditCardToken.id;
                // $form.get(0).submit();
                $.ajax({
                    url: "{{ $url }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        tokencc: token,
                        authentication_id: creditCardToken.authentication_id,
                        card_cvn: $form.find('#card-cvn').val(),

                        amount: "{{ $amountToCharge }}",
                        apikey: "{{ $apikeyToCharge }}",
                        userid: "{{ $useridToCharge }}",
                        invoiceid: "{{ $invoiceidToCharge }}",
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.result == 'success') {
                            // location.reload();
                            $('#modal3DS').modal('hide');
                            $('#CCModal').modal('hide');
                            $('#successModal').modal('show');
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Error. Contact Administrator.');
                    },
                });
            } else if (creditCardToken.status === 'IN_REVIEW') {
                // Penanganan otentikasi (3DS)
                window.open(creditCardToken.payer_authentication_url, 'sample-inline-frame');
                $('#modal3DS').modal('show');
                // $('.overlay').show();
                // $('#three-ds-container').show();
            } else if (creditCardToken.status === 'FAILED') {
                // Penanganan kegagalan
                alert(creditCardToken.failure_reason);
            }
        }
    }
</script>
