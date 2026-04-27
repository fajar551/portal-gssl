<link href="{{ \Module::asset('virtualizor:slider/css/style.css') }}" rel="stylesheet">
<div class="card p-3">
   <div class="row">
      <div class="col-md-12">
         <h3 class="mb-3 text-qw">Additional Addons</h3>
         <form action class="form" method="POST" id="setupVPSform">
            @csrf
            <div class="form-group">
               <label for="ram">RAM</label>
               <div class="range">
                  <input type="range" min="0" max="8" value="0" name="ram" id="ram">
               </div>
               <div id="ramInput" class="range-labels d-flex justify-content-between">
                  <span>0</span>
                  <span>1</span>
                  <span>2</span>
                  <span>3</span>
                  <span>4</span>
                  <span>5</span>
                  <span>6</span>
                  <span>7</span>
                  <span>8</span>
               </div>
            </div>
            <div class="form-group">
               <label for="cpu">Cores</label>
               <div class="range">
                  <input type="range" min="0" max="4" value="0" name="cpu" id="cpu">
               </div>
               <div class="range-labels d-flex justify-content-between">
                  <span>0</span>
                  <span>1</span>
                  <span>2</span>
                  <span>3</span>
                  <span>4</span>
               </div>
            </div>
            <input name="product" type="hidden" value="{{ $pid }}">
            <input name="user" type="hidden" value="{{ $userid }}">
      </div>
      <div class="col-6">
         <button type="submit" class="btn btn-success-qw px-5" id="btn-update">Update</button>
      </div>
      <div class="col-6">
         <button type="button" class="btn btn-outline-danger px-3 float-right" id="btn-cancel">Cancel VPS</button>
      </div>
      </form>
   </div>
</div>
<script src="{{ \Module::asset('virtualizor:slider/js/slider-config.js') }}"></script>
<script>
   var id = '{{ $pid }}'
   var user = '{{ $userid }}'
</script>
<script type="text/javascript">
   $(document).ready(function() {

      var ramValue = $("#ram").val();
      var cpuValue = $("#cpu").val();
      // var hddValue = $("#hdd").val();
      /* console.log(hddValue); */
      //   console.log(ramValue);
      var bandwidthValue = $("#bandwidth").val();
      /* $("#ram").slider({
          ticks: [1, 2, 4, 8, 16],
          ticks_labels: ['1 GB', '2 GB', '4 GB', '8 GB', '16 GB'],
          ticks_snap_bounds: 30,
          value : ramValue 		
      }); */
      //   $("#ram").slider({
      //      ticks: [0, 1, 2, 3, 4, 5, 6, 7, 8],
      //      ticks_labels: ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
      //      ticks_snap_bounds: 30,
      //      value: ramValue
      //   });
      //   $("#hdd").slider({
      //      ticks: [1, 10, 20, 30, 40],
      //      ticks_labels: ['0 GB', '10 GB', '20 GB', '30 GB', '40 GB'],
      //      ticks_snap_bounds: 30,
      //      value: hddValue
      //   });
      //   $("#cpu").slider({
      //      ticks: [0, 1, 2, 3, 4],
      //      ticks_labels: ['0', '1', '2', '3', '4'],
      //      ticks_snap_bounds: 30,
      //      value: cpuValue
      //   });
      //   $("#bandwidth").slider({
      //      ticks: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      //      ticks_labels: ['0 GB', '1 GB', '2 GB', '3 GB', '4 GB', '5 GB', '6 GB', '7 GB', '8 GB', '9 GB',
      //         '10 GB'
      //      ],
      //      ticks_snap_bounds: 30,
      //      value: bandwidthValue
      //   });

      $.LoadingOverlaySetup({
         background: "rgba(37, 43, 59, 0.7)",
         image: '',
         fontawesomeColor: "#EC782A",
         fontawesome: 'fa fa-cog fa-spin',
      });
      /*ajax */
      $("#setupVPSform").submit(function() {
         preload();
         var ramValue = $("#ram").val();
         var cpuValue = $("#cpu").val();
         var xajaxFile = route('virtualizor.vpssetup');
         $('.msg-alert').html();
         Swal.fire({
            title: 'Are you sure?',
            text: "Your VM will be restarted, continue?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, continue!'
         }).then((result) => {
            if (result.isConfirmed) {
               $.LoadingOverlay('show');
               $.ajax({
                  type: 'POST',
                  url: xajaxFile,
                  data: $("#setupVPSform").serialize(),
                  dataType: 'json',
                  success: function(data) {
                     $.LoadingOverlay('hide');
                     if (!data.error) {
                        $(":input", "#setupVPSform")
                           .not(":button, :submit, :reset, :hidden")
                           .val("")
                           .removeAttr("checked")
                           .removeAttr("selected");
                        /* $(".msg-alert").html('<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="glyphicon glyphicon-ok-circle iconleft" aria-hidden="true"></span> '+data.alert+"</div>"); */
                        $('#loader-wrapper').fadeOut();
                        Swal.fire(
                           'Updated!',
                           'Your VM has been updated',
                           'success'
                        )
                        loadVPS();
                     } else {
                        $.LoadingOverlay('hide');
                        $(".msg-alert").html(
                           '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="glyphicon glyphicon-exclamation-sign iconleft" aria-hidden="true"></span> ' +
                           data.alert + "</div>");
                        swal({
                           title: "Error",
                           text: data.alert,
                           icon: "error",
                           button: "Ok",
                        });
                     }
                  },
                  error: function(err) {
                     $.LoadingOverlay('hide');
                     Swal.fire({
                        title: "Something went wrong!",
                        text: 'Can\'t process your request. CODE:' + err.statusText +
                           '(' + err.status + ')',
                        icon: "error",
                     });
                  }
               })

            }
         })
         return false;
      });

      loadVPS();

      /*cencel vps*/
      $("#btn-cancel").click(function() {
         const token = '{{ csrf_token() }}'
         // console.log(token);
         Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
         }).then(async (result) => {
            if (result.isConfirmed) {
               $.LoadingOverlay('show');
               $.ajax({
                  type: "POST",
                  url: route('virtualizor.cancelvps'),
                  data: $.param({
                     _token: token,
                     id: id,
                     user: user,
                  }),
                  dataType: 'json',
                  success: async function(data) {
                     console.log(data);
                     if (!data.error) {
                        $.LoadingOverlay('hide');
                        await Swal.fire(
                           'Deleted!',
                           data.alert,
                           'success'
                        )
                        await location.reload()
                     } else {
                        $.LoadingOverlay('hide');
                        Swal.fire({
                           title: "Error",
                           text: data.alert,
                           icon: "error",
                        });

                     }
                  },
                  error: function(err) {
                     $.LoadingOverlay('hide');
                     console.log(err);
                     Swal.fire({
                        title: "Error",
                        text: err.status,
                        icon: "error",
                     });
                  }
               });
            }
         })
      })
   })
   //documenready



   var preload = function() {
      $('#loader-wrapper').fadeIn(1000).fadeOut();
   };

   var loadVPS = function() {
      $.ajax({
         type: "POST",
         url: route('virtualizor.checkvps'),
         data: $.param({
            id: id,
            user: user
         }),
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         dataType: 'json',
         success: function(data) {
            // console.log(data);
            if (!data.error) {
               $("#ram").val(data.ram);
               $("#cpu").val(data.cores);
               /*$("#hdd").val(data.hdd);
               $("#cpu").val(data.cores);
               $("#bandwidth").val(data.bandwidth);	 */
               // $('#ram').slider('setValue', data.ram);
               // $('#hdd').slider('setValue', data.hdd);
               // $('#cpu').slider('setValue', data.cores);
               // //$('#cpu').slider('setValue', 2);
               // $('#bandwidth').slider('setValue', data.bandwidth);

            }
         }
      });
   };
</script>
