<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Admin;
use App\Models\Legacy\Sede;
use App\Models\Legacy\Turno;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use ConAccesso;

    public function render()
    {
        $this->soloStaff();

        $clientiVisibili = $this->clientiVisibili();
        $idClientiVisibili = (clone $clientiVisibili)->select('tc_clienti.id');
        $turniVisibili = Turno::query()->whereIn('id_cliente', clone $idClientiVisibili);
        $oggi = today();

        $metriche = [
            ['nome' => 'Clienti', 'valore' => $clientiVisibili->count(), 'destinazione' => 'clienti'],
            ['nome' => 'Sedi', 'valore' => Sede::query()->whereIn('id_cliente', clone $idClientiVisibili)->count(), 'destinazione' => 'sedi'],
            ['nome' => 'Operatori attivi', 'valore' => Admin::query()->where('user_type', 4)->where('attivo', 'Y')->count(), 'destinazione' => 'operatori'],
            ['nome' => 'Turni oggi', 'valore' => (clone $turniVisibili)->where('data_inizio', '<=', $oggi->toDateString())->where('data_fine', '>=', $oggi->toDateString())->count(), 'destinazione' => 'turni'],
        ];

        $prossimiTurni = (clone $turniVisibili)
            ->with(['operatore', 'cliente', 'sede'])
            ->where('data_inizio', '>=', $oggi->toDateString())
            ->orderBy('data_inizio')
            ->orderBy('ora_inizio')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', compact('metriche', 'prossimiTurni'));
    }
}
