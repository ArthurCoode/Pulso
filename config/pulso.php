<?php

return [

    // Em produção deve ser false: impede que o monitor seja usado para
    // fazer requisições à rede interna do servidor (proteção SSRF).
    // Em desenvolvimento, coloque true no .env para monitorar localhost.
    'allow_private_hosts' => env('PULSO_ALLOW_PRIVATE_HOSTS', false),

    'user_agent' => env('PULSO_USER_AGENT', 'PulsoMonitor/1.0'),

];