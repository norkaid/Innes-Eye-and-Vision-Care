@if (config('innes.demo_notice'))
    <div class="bg-sand text-navy">
        <p class="container-page flex items-center gap-2 py-2 text-xs sm:text-sm">
            <x-icon name="info" class="h-4 w-4 shrink-0" />
            <span><strong>Demo website:</strong> branch details, services, products and articles shown here are sample content awaiting confirmation by the centre.</span>
        </p>
    </div>
@endif
