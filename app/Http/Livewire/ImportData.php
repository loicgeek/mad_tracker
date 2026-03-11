<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\ClientsImport;
use App\Imports\FournisseursImport;
use App\Imports\DossiersImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportData extends Component
{
    use WithFileUploads;

    public string $importType = 'dossiers'; // dossiers | clients | fournisseurs
    public $file = null;

    public bool $importing = false;
    public bool $done = false;
    public array $results = [];
    public array $importErrors = [];
    public int $imported = 0;
    public int $skipped = 0;

    protected function rules(): array
    {
        return [
           'file' => 'required|mimes:xlsx,xls,csv|max:10240',
           'importType' => 'required|in:dossiers,clients,fournisseurs',
        ];
    }

    public function updatedImportType(): void
    {
        $this->reset(['file', 'results', 'importErrors', 'done', 'imported', 'skipped']);
    }

    public function import(): void
    {
         // Vérification défensive avant validate()
    if (!$this->file || !$this->file->exists()) {
        $this->dispatch('notify', type: 'error', message: 'Fichier introuvable, veuillez le sélectionner à nouveau.');
        $this->file = null;
        return;
    }
        $this->validate();

        $this->importing = true;
        $this->importErrors = [];
        $this->results = [];

        try {
            $path = $this->file->store('imports', 'local');
            $fullPath = Storage::disk('local')->path($path);

            $import = match ($this->importType) {
                'clients'       => new ClientsImport(),
                'fournisseurs'  => new FournisseursImport(),
                'dossiers'      => new DossiersImport(),
            };

            Excel::import($import, $fullPath);

            $this->imported = $import->getImportedCount();
            $this->skipped  = $import->getSkippedCount();
            $this->importErrors   = $import->getErrors();
            $this->done     = true;

            Storage::disk('local')->delete($path);

            $this->dispatch('notify', type: 'success',
                message: "{$this->imported} ligne(s) importée(s), {$this->skipped} ignorée(s).");
        } catch (\Throwable $e) {
            $this->importErrors[] = "Erreur fatale : {$e->getMessage()}";
            $this->dispatch('notify', type: 'error', message: 'Erreur lors de l\'import.');
        } finally {
            $this->importing = false;
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $export = match ($this->importType) {
            'clients'      => new \App\Exports\ClientsTemplateExport(),
            'fournisseurs' => new \App\Exports\FournisseursTemplateExport(),
            'dossiers'     => new \App\Exports\DossiersTemplateExport(),
        };

        return Excel::download($export, "template-{$this->importType}.xlsx");
    }

    public function render()
    {
        return view('livewire.import-data')
            ->layout('layouts.app', ['title' => 'Import de données']);
    }
}
