<?php

namespace App\Http\Controllers;

use App\Models\Simulation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SimulationReportController extends Controller
{
    public function show($id)
    {
        $simulation = Simulation::with('answers')
            ->findOrFail($id);

        // Ambil data untuk line chart (max 10 simulasi sebelumnya)
        $previousSimulations = Simulation::where('user_id', $simulation->user_id)
            ->where('id', '<', $id)
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get(['id', 'total_score', 'completed_at'])
            ->reverse()
            ->values();

        // Persiapkan data untuk chart
        $chartData = [
            'scores' => [
                'reading' => $simulation->reading_score,
                'listening' => $simulation->listening_score,
                'speaking' => $simulation->speaking_score,
                'writing' => $simulation->writing_score,
            ],
            'trend_labels' => $previousSimulations->map(fn($s) => $s->completed_at->format('d M'))->toArray(),
            'trend_data' => $previousSimulations->pluck('total_score')->toArray(),
            'micro_skills' => $simulation->micro_skills ?? [],
            'time_analysis' => $simulation->time_analysis ?? [],
        ];

        return view('reports.simulation-detail', compact('simulation', 'chartData', 'previousSimulations'));
    }

    public function exportPdf($id)
    {
        $simulation = Simulation::with('answers')->findOrFail($id);

        $data = [
            'simulation' => $simulation,
            'answers' => $simulation->answers->groupBy('section'),
        ];

        $pdf = Pdf::loadView('reports.simulation-report-pdf', $data);
        
        return $pdf->download('laporan-simulasi-' . $simulation->id . '.pdf');
    }
}
