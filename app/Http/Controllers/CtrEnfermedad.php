<?php

namespace App\Http\Controllers;

use App\Models\Enfermedad;
use PDF;

class CtrEnfermedad extends Controller
{
    public function index()
    {
        return view('enfermedad.index');
    }

    public function show(Enfermedad $enfermedad)
    {
        return view('enfermedad.show', compact('enfermedad'));
    }

    public function exportar()
    {
        $enfermedades = Enfermedad::all();

        $pdf = PDF::loadView('enfermedad.pdf', compact('enfermedades'));
        return $pdf->stream('enfermedades.pdf');
    }
}
