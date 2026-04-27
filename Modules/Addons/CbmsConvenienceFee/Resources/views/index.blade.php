@extends('layouts.basecbms')

@section('title')
    <title>CBMS Auto - CF Configuration</title>
@endsection

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mb-0">Convenience Fee Setting</h2>
                    <small class="text-muted">By CBMS</small>
                </div>
                <div class="col-md-12 mt-3">
                    <form id="cf-form" action="" method="post">
                        @csrf
                        <table class="table table-bordered table-hover w-100 display">
                            <thead>
                                <tr class="bg-primary text-white">
                                    <th>No</th>
                                    <th>Payment Gateway</th>
                                    <th>Percentage Price % (x)</th>
                                    <th>Fixed Price (+)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($activeGateway as $data)
                                    @php
                                        $module = $data["gateway"];
                                        $order = $data["order"];
                                        $name = $data['value'];
                                        $isDataExists = array_key_exists($module, $cfsdata);
                                    @endphp
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$name}}</td>
                                        <td>
                                            <input
                                                type="number"
                                                name="percentage_price[{{$module}}]"
                                                min="0"
                                                max="100"
                                                maxlength="3"
                                                step="any"
                                                id=""
                                                class="form-control form-control-sm input-pp w-25"
                                                @if ($isDataExists)
                                                    value="{{$cfsdata[$module]['percentage_amount']}}"
                                                @endif
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="fixed_price[{{$module}}]"
                                                min="0"
                                                step="any"
                                                id=""
                                                class="form-control form-control-sm input-fp w-25"
                                                @if ($isDataExists)
                                                    value="{{$cfsdata[$module]['fixed_amount']}}"
                                                @endif
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <th colspan="4" class="text-right">
                                    <button class="btn btn-success">Save Change</button>
                                </th>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
    <script src="{{ Theme::asset('js/notify.min.js') }}"></script>
    <script>
        // $(".input-fp, .input-pp").on("keyup", function() {
        //     $form.submit();
        // });
        var $form = $("form#cf-form");
        $.validator.addClassRules("input-fp", {
            min: 0,
            number: true,
            required: false,
        });
        $.validator.addClassRules("input-pp", {
            min: 0,
            max: 100,
            number: true,
            required: false,
        });
        $form.validate({
            normalizer: function (value) {
                return value ? value.trim() : value;
            },
            errorElement: 'small',
            errorClass: "is-invalid text-danger font-weight-normal",
            rules: {},
        });
        $form.on("submit", function(e) {
            e.preventDefault();
            var isFormValid = $form.valid();
            console.log(isFormValid);
            if (!isFormValid) {
                return false;
            }
            var data = $(this).serialize();
            // console.log(data);
            $.ajax({
                url: route('cbmscf.save'),
                type: 'post',
                data: data,
                success: function(res) {
                    console.log(res);
                    if (res.result == 'success') {
                        $.notify(res.message, "success");
                    } else {
                        $.notify(res.message, "error");
                    }
                },
                error: function(xhr, status, error) {
                    var e = JSON.parse(xhr.responseText);
                    $.notify(e.message, "error");
                },
            });
        });
    </script>
@endsection
