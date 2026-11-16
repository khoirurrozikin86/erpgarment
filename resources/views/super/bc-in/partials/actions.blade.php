<div class="d-flex gap-1 text-nowrap">
    @can($module.'.update')
        <a class="btn btn-outline-primary btn-sm" href="{{ route('super.'.($direction ?? 'bc-in').'.'.$module.'.edit', $item->document_id) }}" title="{{ __('ui.common.edit') }}"><i data-feather="edit-2" class="icon-sm"></i></a>
    @endcan
    @can($module.'.delete')
        <form class="delete-bc" method="POST" action="{{ route('super.'.($direction ?? 'bc-in').'.'.$module.'.destroy', $item->document_id) }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger btn-sm" title="{{ __('ui.common.delete') }}"><i data-feather="trash-2" class="icon-sm"></i></button>
        </form>
    @endcan
</div>
