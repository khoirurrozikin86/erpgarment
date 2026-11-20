@push('scripts')
<script>
    @if(session('success'))
        Swal.fire({icon: 'success', title: @json(__('ui.workflow.success')), text: @json(session('success'))});
    @elseif($errors->any())
        Swal.fire({
            icon: 'error',
            title: @json(__('ui.workflow.failure')),
            text: @json(implode("\n", $errors->all())),
            customClass: {htmlContainer: 'text-pre-line'},
        });
    @endif

    $(document).on('submit', 'form.bom-delete-form', function (event) {
        event.preventDefault();
        const form = this;

        Swal.fire({
            icon: 'warning',
            title: @json(__('ui.common.confirm_delete')),
            text: form.dataset.confirm,
            showCancelButton: true,
            confirmButtonText: @json(__('ui.common.delete')),
            cancelButtonText: @json(__('ui.common.cancel')),
            confirmButtonColor: '#d33',
        }).then(result => {
            if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
        });
    });
</script>
@endpush

@push('styles')
<style>
    .swal2-html-container.text-pre-line { white-space: pre-line; }
</style>
@endpush
