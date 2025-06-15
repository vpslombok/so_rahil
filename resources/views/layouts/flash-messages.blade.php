@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if (session('success_message_product') || session('success_message'))
        Swal.fire({
            icon: 'success',
            title: 'Sukses!',
            text: '{{ session('success_message_product') ?? session('success_message') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        @endif

        @if (session('error_message_product') || session('error_message'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error_message_product') ?? session('error_message') }}',
        });
        @endif

        @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: `<ul class="text-left">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                </ul>`,
        });
        @endif

        @if (session('status'))
        Swal.fire({
            icon: 'info',
            title: 'Info',
            text: '{{ session('status') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });
        @endif
    });
</script>
@endpush

{{-- Include the flash messages --}}