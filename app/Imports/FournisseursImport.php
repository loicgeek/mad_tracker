<?php

namespace App\Imports;

use App\Models\Fournisseur;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;

class FournisseursImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use WithImportTracking, SkipsErrors;

    /**
     * Expected columns:
     *   nom*, pays, ville, contact_nom, contact_email, notes
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $nom = trim((string) ($row['nom'] ?? $row['fournisseur'] ?? $row['supplier'] ?? ''));
            if (empty($nom)) {
                $this->addError("Ligne {$rowNum} : colonne 'nom' manquante — ignorée.");
                $this->incrementSkipped();
                continue;
            }

            Fournisseur::updateOrCreate(
                ['nom' => $nom],
                [
                    'pays'          => trim((string) ($row['pays'] ?? $row['country'] ?? '')) ?: null,
                    'ville'         => trim((string) ($row['ville'] ?? $row['city'] ?? '')) ?: null,
                    'contact_nom'   => trim((string) ($row['contact_nom'] ?? $row['contact'] ?? '')) ?: null,
                    'contact_email' => trim((string) ($row['contact_email'] ?? $row['email'] ?? '')) ?: null,
                    'notes'         => trim((string) ($row['notes'] ?? $row['remarques'] ?? '')) ?: null,
                ]
            );

            $this->incrementImported();
        }
    }
}
