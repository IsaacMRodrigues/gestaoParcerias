@props([
    'href' => '#',
    'label' => '',
])

<a href="{{ $href }}"
   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 bg-white hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50/40 transition">
    <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
    {{ $label }}
</a>
