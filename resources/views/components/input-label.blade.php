@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-gray-800']) }}>
    {{ $value ?? $slot }}
</label>
