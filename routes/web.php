<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\DossierIndex;
use App\Http\Livewire\DossierForm;
use App\Http\Livewire\DossierShow;
use App\Http\Livewire\Analyse;
use App\Http\Livewire\ClientIndex;
use App\Http\Livewire\FournisseurIndex;
use App\Http\Livewire\ImportData;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Livewire\TransporteurIndex;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// App (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/dossiers', DossierIndex::class)->name('dossiers.index');
    Route::get('/dossiers/nouveau', DossierForm::class)->name('dossiers.create');
    Route::get('/dossiers/{id}', DossierShow::class)->name('dossiers.show');
    Route::get('/dossiers/{id}/modifier', DossierForm::class)->name('dossiers.edit');

    Route::get('/analyses', Analyse::class)->name('analyses');

    // Référentiels
    Route::get('/clients', ClientIndex::class)->name('clients.index');
    Route::get('/fournisseurs', FournisseurIndex::class)->name('fournisseurs.index');
    Route::get('/transporteurs', TransporteurIndex::class)->name('transporteurs.index');

    // Notifications
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Import
    Route::get('/import', ImportData::class)->name('import');
    Route::get('/import/template', function (\Illuminate\Http\Request $request) {
        $type = $request->query('type', 'dossiers');
        $export = match($type) {
            'clients'      => new \App\Exports\ClientsTemplateExport(),
            'fournisseurs' => new \App\Exports\FournisseursTemplateExport(),
            default        => new \App\Exports\DossiersTemplateExport(),
        };
        return \Maatwebsite\Excel\Facades\Excel::download($export, "template-{$type}.xlsx");
    })->name('import.template');

    // Exports
    Route::get('/export/dossiers', [ExportController::class, 'dossiers'])->name('export.dossiers');
    Route::get('/export/analyses', [ExportController::class, 'analyses'])->name('export.analyses');
    Route::get('/export/dossier/{id}/pdf', [ExportController::class, 'dossierPdf'])->name('export.dossier.pdf');
});
