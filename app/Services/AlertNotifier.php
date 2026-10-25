<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AlertNotifier
{
    public function monitorDown(Monitor $monitor, Incident $incident): void
    {
        $subject = "🔴 {$monitor->name} está fora do ar";

        $body = implode("\n", [
            $subject,
            "URL: {$monitor->url}",
            'Motivo: '.($incident->cause ?? 'não informado'),
            'Desde: '.$incident->started_at->format('d/m/Y H:i:s'),
        ]);

        $this->send($subject, $body);
    }

    public function monitorRecovered(Monitor $monitor, Incident $incident): void
    {
        $minutes = max(1, (int) round(abs($incident->started_at->diffInMinutes($incident->resolved_at))));

        $subject = "🟢 {$monitor->name} voltou ao ar";

        $body = implode("\n", [
            $subject,
            "URL: {$monitor->url}",
            "Ficou fora por cerca de {$minutes} min.",
        ]);

        $this->send($subject, $body);
    }

    private function send(string $subject, string $body): void
    {
        $this->sendTelegram($body);
        $this->sendMail($subject, $body);
    }

    private function sendTelegram(string $body): void
    {
        $token = config('pulso.alerts.telegram_token');
        $chatId = config('pulso.alerts.telegram_chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $body,
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::warning('Pulso: Telegram respondeu com status '.$response->status());
            }
        } catch (Throwable $e) {
            // A URL da API contém o token: ele é mascarado antes de ir para o log.
            Log::warning('Pulso: falha ao enviar para o Telegram: '.str_replace($token, '***', $e->getMessage()));
        }
    }

    private function sendMail(string $subject, string $body): void
    {
        $to = config('pulso.alerts.mail_to');

        if (! $to) {
            return;
        }

        try {
            Mail::raw($body, fn ($message) => $message->to($to)->subject($subject));
        } catch (Throwable $e) {
            Log::warning('Pulso: falha ao enviar e-mail de alerta: '.$e->getMessage());
        }
    }
}