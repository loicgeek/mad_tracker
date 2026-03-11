<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;

class ClientsImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use WithImportTracking, SkipsErrors;

    /**
     * Expected columns (case-insensitive, flexible):
     *   nom*, pays, contact_nom, contact_email, contact_phone, notes
     *
     * * = required
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because of header row

            $nom = trim((string) ($row['nom'] ?? $row['client'] ?? ''));
            if (empty($nom)) {
                $this->addError("Ligne {$rowNum} : colonne 'nom' manquante ou vide — ignorée.");
                $this->incrementSkipped();
                continue;
            }

            // updateOrCreate to allow re-imports without duplicates
            Client::updateOrCreate(
                ['nom' => $nom],
                [
                    'pays'          => trim((string) ($row['pays'] ?? $row['country'] ?? '')) ?: null,
                    'contact_nom'   => trim((string) ($row['contact_nom'] ?? $row['contact'] ?? '')) ?: null,
                    'contact_email' => trim((string) ($row['contact_email'] ?? $row['email'] ?? '')) ?: null,
                    'contact_phone' => trim((string) ($row['contact_phone'] ?? $row['phone'] ?? $row['tel'] ?? '')) ?: null,
                    'notes'         => trim((string) ($row['notes'] ?? $row['remarques'] ?? '')) ?: null,
                ]
            );

            $this->incrementImported();
        }
    }
}
