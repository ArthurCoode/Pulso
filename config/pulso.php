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

    // Dias de checagens guardados. O comando monitors:prune nunca usa menos
    // de 90, porque a status page mostra 90 dias.
    'retention_days' => (int) env('PULSO_RETENTION_DAYS', 120),

    // Fuso usado apenas para EXIBIR datas. O banco continua em UTC.
    'display_timezone' => env('PULSO_TIMEZONE', 'America/Bahia'),

    // Proteção do painel (HTTP Basic). Se vazio: liberado em ambiente local,
    // bloqueado em qualquer outro ambiente.
    'admin' => [
        'user' => env('PULSO_ADMIN_USER'),
        'password' => env('PULSO_ADMIN_PASSWORD'),
    ],

    // Textos da status page pública.
    'status_page' => [
        'title' => env('PULSO_STATUS_TITLE', 'Status dos serviços'),
        'description' => env('PULSO_STATUS_DESCRIPTION', 'Situação atual e histórico de disponibilidade.'),
    ],

    'alerts' => [
        'mail_to' => env('PULSO_ALERT_EMAIL'),
        'telegram_token' => env('PULSO_TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('PULSO_TELEGRAM_CHAT_ID'),
    ],

];