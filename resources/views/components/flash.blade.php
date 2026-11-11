@if (session('success'))
    <x-alert type="success" {{ $attributes }}>{{ session('success') }}</x-alert>
@endif
@if (session('error'))
    <x-alert type="error" {{ $attributes }}>{{ session('error') }}</x-alert>
@endif
@if ($errors->any())
    <x-alert type="error" title="Please check the form" {{ $attributes }}>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </x-alert>
@endif
