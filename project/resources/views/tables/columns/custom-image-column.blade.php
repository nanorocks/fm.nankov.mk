<div class="flex items-center gap-2">
    @if ($getState())
        <img src="{{ $getState() }}" alt="Image" class="h-10 w-10 rounded-full object-cover">
    @else
        <span>No Image</span>
    @endif
</div>
