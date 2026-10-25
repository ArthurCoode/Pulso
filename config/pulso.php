<?php

return [

    // Em produção deve ser false: impede que o monitor seja usado para
    // fazer requisições à rede interna do servidor (proteção SSRF).
    // Em desenvolvimento, coloque true no .env para monitorar localhost.
    'allow_private_hosts' => env('PULSO_ALLOW_PRIVATE_HOSTS', false),

    'user_agent' => env('PULSO_USER_AGENT', 'PulsoMonitor/1.0'),

    // Quantas falhas seguidas são necessárias para declarar um monitor "down".
    // Evita alarme falso por uma oscilação de rede isolada.
    'failure_threshold' => (int) env('PULSO_FAILURE_THRESHOLD', 3),

    // Um Job que esperou mais que isso (em segundos) na fila é descartado:
    // a checagem já está velha e o Scheduler despacha uma nova em seguida.
    'max_job_age' => (int) env('PULSO_MAX_JOB_AGE', 120),

    'alerts' => [
        'mail_to' => env('PULSO_ALERT_EMAIL'),
        'telegram_token' => env('PULSO_TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('PULSO_TELEGRAM_CHAT_ID'),
    ],

];