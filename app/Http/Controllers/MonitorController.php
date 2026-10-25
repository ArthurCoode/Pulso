<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Http\Requests\MonitorRequest;
use App\Models\Check;
use App\Models\Monitor;
use App\Services\UptimeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(UptimeCalculator $uptime): View
    {
        $monitors = Monitor::with('latestCheck')->orderBy('name')->get();

        $summary = [
            'total' => $monitors->count(),
            'up' => $monitors->filter(fn (Monitor $m) => $m->is_active && $m->status === MonitorStatus::Up)->count(),
            'down' => $monitors->filter(fn (Monitor $m) => $m->is_active && $m->status === MonitorStatus::Down)->count(),
            'other' => $monitors->filter(fn (Monitor $m) => ! $m->is_active || $m->status === MonitorStatus::Unknown)->count(),
        ];

        return view('monitors.index', [
            'monitors' => $monitors,
            'summary' => $summary,
            'uptime' => $uptime->percentagesForAll(24),
        ]);
    }

    public function create(): View
    {
        // O model novo já traz os valores padrão definidos em $attributes.
        return view('monitors.create', ['monitor' => new Monitor]);
    }

    public function store(MonitorRequest $request): RedirectResponse
    {
        $monitor = Monitor::create($request->validated());

        return redirect()
            ->route('monitors.show', $monitor)
            ->with('status', 'Monitor criado. A primeira checagem acontece em até 1 minuto.');
    }

    public function show(Monitor $monitor, UptimeCalculator $uptime): View
    {
        // Série do gráfico: últimas 60 checagens bem-sucedidas, da mais antiga para a mais nova.
        $series = Check::query()
            ->where('monitor_id', $monitor->id)
            ->where('is_successful', true)
            ->whereNotNull('response_time_ms')
            ->latest('id')
            ->limit(60)
            ->get(['response_time_ms', 'checked_at'])
            ->reverse()
            ->values();

        return view('monitors.show', [
            'monitor' => $monitor->load('openIncident'),
            'uptime24' => $uptime->percentage($monitor, 24),
            'uptime7' => $uptime->percentage($monitor, 24 * 7),
            'uptime30' => $uptime->percentage($monitor, 24 * 30),
            'avgResponse' => $uptime->averageResponseMs($monitor, 24),
            'series' => $series,
            'checks' => $monitor->checks()->latest('id')->limit(30)->get(),
            'incidents' => $monitor->incidents()->latest('id')->limit(10)->get(),
        ]);
    }

    public function edit(Monitor $monitor): View
    {
        return view('monitors.edit', ['monitor' => $monitor]);
    }

    public function update(MonitorRequest $request, Monitor $monitor): RedirectResponse
    {
        $monitor->fill($request->validated());

        // Mudou a URL: o histórico de estado deixa de valer. Fecha o incidente
        // aberto (sem alerta) e recomeça do zero, senão ele ficaria aberto para sempre.
        if ($monitor->isDirty('url')) {
            $monitor->openIncident()->update(['resolved_at' => now()]);
            $monitor->status = MonitorStatus::Unknown;
            $monitor->consecutive_failures = 0;
            $monitor->next_check_at = null;
        }

        $monitor->save();

        return redirect()
            ->route('monitors.show', $monitor)
            ->with('status', 'Monitor atualizado.');
    }

    public function destroy(Monitor $monitor): RedirectResponse
    {
        // Os checks e incidentes são apagados junto (cascadeOnDelete na migration).
        $monitor->delete();

        return redirect()
            ->route('monitors.index')
            ->with('status', 'Monitor removido.');
    }

    public function toggle(Monitor $monitor): RedirectResponse
    {
        $monitor->is_active = ! $monitor->is_active;

        // Ao retomar, zera o próximo horário para checar já no próximo minuto.
        if ($monitor->is_active) {
            $monitor->next_check_at = null;
        }

        $monitor->save();

        return back()->with('status', $monitor->is_active ? 'Monitor retomado.' : 'Monitor pausado.');
    }
}