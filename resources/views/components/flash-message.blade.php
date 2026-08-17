{{-- Avisos de sessão. Inclui 'info' e 'warning', que já eram disparados pelo
     sistema (ex.: EnsureIsStaff) mas não tinham onde aparecer. --}}
@php
    $tipos = [
        'success' => [
            'classes' => 'text-brand-800 bg-brand-50 border-brand-200',
            'icone'   => 'text-brand-600',
            'path'    => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'error' => [
            'classes' => 'text-red-800 bg-red-50 border-red-200',
            'icone'   => 'text-red-600',
            'path'    => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        ],
        'warning' => [
            'classes' => 'text-accent-900 bg-accent-50 border-accent-200',
            'icone'   => 'text-accent-600',
            'path'    => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        'info' => [
            'classes' => 'text-brand-900 bg-brand-50 border-brand-200',
            'icone'   => 'text-brand-600',
            'path'    => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        ],
    ];
@endphp

@foreach($tipos as $chave => $estilo)
    @if(session($chave))
        <div class="mb-4 flex items-start gap-3 p-4 text-sm font-medium border rounded-xl {{ $estilo['classes'] }}"
             role="alert">
            <svg class="w-5 h-5 shrink-0 {{ $estilo['icone'] }}" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="1.9">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $estilo['path'] }}"/>
            </svg>
            <span class="leading-relaxed">{{ session($chave) }}</span>
        </div>
    @endif
@endforeach
