@props(['action', 'label' => 'Delete', 'confirm' => 'Are you sure?', 'method' => 'DELETE', 'class' => 'btn-danger btn-sm'])
<form method="POST" action="{{ $action }}" class="inline" x-data @submit="if (!confirm(@js($confirm))) $event.preventDefault()">
    @csrf @method($method)
    <button type="submit" class="{{ $class }}">{{ $label }}</button>
</form>
