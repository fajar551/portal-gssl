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