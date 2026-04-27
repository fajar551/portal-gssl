@if (!empty($documents))
    @foreach ($documents as $key => $doc)
        <div class="row mb-3 d-flex justify-content-center text-center slide-in-right">
            @php
                $filePath = '/home/clientgudang/domains/client.gudangssl.id/public_html/Files/' . $doc['file'];
                $fileUrl = 'https://' . $_SERVER['SERVER_NAME'] . '/Files/' . $doc['file'];
            @endphp

            @if (file_exists($filePath))
                <img class="img img-approval fade-in-image" src="{{ $fileUrl }}" alt="Document Image"
                    data-bs-toggle="modal" data-bs-target="#modalApproval">
            @else
                <div class="no-image-placeholder">
                    <p class="d-flex justify-content-center text-center text-center">Image not available</p>
                    <p>Path Checked: {{ $filePath }}</p>
                </div>
            @endif
        </div>
        <label class="slide-in-left">Catatan untuk Klien</label>
        <textarea class="form-control mb-3 slide-in-left" id="note-{{ $key }}"></textarea>
        <div class="text-center slide-in-right">
            <button class="btn btn-success process-document" data-key="{{ $key }}" data-status=1>
                <i class="fa fa-check"></i> Accept
            </button>
            <button class="btn btn-danger process-document" data-key="{{ $key }}" data-status=2>
                <i class="fa fa-times"></i> Reject
            </button>
        </div>
    @endforeach
@else
    <p class="text-center">No documents for domains: {{ $domain }}</p>
@endif

<script>
    document.querySelectorAll('.process-document').forEach(button => {
        button.addEventListener('click', function() {
            const key = this.dataset.key;
            const status = this.dataset.status;
            const note = document.getElementById('note-' + key).value;
            const domain = '{{ $domain }}';
            const parentDiv = this.parentElement;

            parentDiv.querySelectorAll('button').forEach(btn => btn.disabled = true);

            fetch("{{ route('privatens_registrar.process_document') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        domain: domain,
                        key: key,
                        status: status,
                        ket: note
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1000) {
                        const successMessage = status == 1 ?
                            'Document status has been accepted.' :
                            'Document status has been rejected.';

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: successMessage,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.msg,
                            showConfirmButton: true,
                        });

                        parentDiv.querySelectorAll('button').forEach(btn => btn.disabled = false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.',
                        showConfirmButton: true,
                    });

                    parentDiv.querySelectorAll('button').forEach(btn => btn.disabled = false);
                });
        });
    });
</script>
