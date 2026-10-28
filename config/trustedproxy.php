<?php

// A CDN da hospedagem fica entre o usuário e o servidor: sem confiar nela, o IP de toda requisição
// é o da CDN. Faixas separadas por vírgula; vazio, nenhum proxy é confiável. Só o X-Forwarded-For
// é lido (bootstrap/app.php).
return [
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
