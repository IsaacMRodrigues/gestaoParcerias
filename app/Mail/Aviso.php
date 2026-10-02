<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O aviso por e-mail, um formato para todos. Vai para a fila depois do commit (afterCommit);
 * quem recebe o quê decide App\Support\Avisos.
 */
class Aviso extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** Segundos entre as tentativas: dá tempo de um soluço do SMTP passar. */
    public array $backoff = [60, 300];

    /**
     * @param  string[]  $linhas  parágrafos do corpo, em texto simples
     */
    public function __construct(
        public string $assunto,
        public string $titulo,
        public array $linhas,
        public ?string $url = null,
        public string $botao = 'Abrir no sistema',
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Parcerias] ' . $this->assunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.aviso', text: 'emails.aviso-texto');
    }
}
