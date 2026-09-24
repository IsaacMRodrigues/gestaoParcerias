<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O aviso por e-mail — um só formato para todos os avisos do sistema.
 *
 * Vai para a fila e sai pelo agendamento (routes/console.php): a tela não
 * espera o servidor de e-mail, e uma falha dele é tentada de novo em vez de
 * derrubar a ação. `afterCommit`: só entra na fila depois que a gravação que
 * o motivou foi confirmada — ação desfeita não avisa de nada.
 *
 * Quem decide quem recebe e o que se diz é App\Support\Avisos.
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
