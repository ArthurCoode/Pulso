<?php

namespace App\Http\Controllers;

use App\Services\StatusPageData;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    public const CACHE_KEY = 'pulso.status_page';

    public function __invoke(StatusPageData $data): View
    {
        // Mesmo com muitas visitas, o banco é consultado no máximo 1x por minuto.
        $page = Cache::remember(self::CACHE_KEY, 60, fn () => $data->build());

        return view('status', $page + [
            'title' => config('pulso.status_page.title'),
            'description' => config('pulso.status_page.description'),
        ]);
    }
}