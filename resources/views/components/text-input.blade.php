@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-500']) }}>
