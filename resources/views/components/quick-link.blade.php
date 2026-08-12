@props([
    'href' => '#',
    'label' => '',
])

<a href="{{ $href }}"
   class="group inline-flex items-center gap-2.5 pl-3 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white
          text-sm font-semibold text-gray-700 shadow-sm
          hover:border-brand-400 hover:text-brand-800 hover:shadow transition duration-200
          focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
    <span class="w-6 h-6 shrink-0 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center
                 group-hover:bg-brand-600 group-hover:text-white transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </span>
    {{ $label }}
</a>
