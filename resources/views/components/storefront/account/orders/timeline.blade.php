@props(['histories'])
<div class="space-y-0">
    @forelse($histories as $history)
        <div class="relative grid grid-cols-[28px_1fr] gap-3 pb-6 last:pb-0">
            <div class="relative flex justify-center"><span class="z-10 mt-1 h-3 w-3 rounded-full bg-brand-red ring-4 ring-red-50"></span>@unless($loop->last)<span class="absolute left-1/2 top-4 h-full w-px -translate-x-1/2 bg-slate-200"></span>@endunless</div>
            <div><time class="text-xs font-black uppercase tracking-wide text-slate-400">{{ $history->occurred_at?->format('M d, Y · g:i A') }}</time><h4 class="mt-1 font-black text-brand-ink">{{ $history->title }}</h4>@if($history->description)<p class="mt-1 text-sm leading-6 text-slate-600">{{ $history->description }}</p>@endif</div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No status activity has been recorded yet.</p>
    @endforelse
</div>
