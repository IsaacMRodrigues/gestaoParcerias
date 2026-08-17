{{-- Usa o sistema de botões de resources/css/app.css --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary']) }}>
    {{ $slot }}
</button>
