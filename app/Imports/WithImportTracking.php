<?php

namespace App\Imports;

trait WithImportTracking
{
    private int $importedCount = 0;
    private int $skippedCount = 0;
    private array $importErrors = [];

    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int  { return $this->skippedCount; }
    public function getErrors(): array      { return $this->importErrors; }

    protected function incrementImported(): void { $this->importedCount++; }
    protected function incrementSkipped(): void  { $this->skippedCount++; }
    protected function addError(string $msg): void { $this->importErrors[] = $msg; }

    protected function cleanDate(?string $value): ?string
    {
        if (empty($value)) return null;
        // Try multiple formats: d/m/Y, Y-m-d, d-m-Y, Excel serial
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($value));
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        // Excel date serial number
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)
                    ->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        return null;
    }

    protected function cleanBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        $str = strtolower(trim((string) $value));
        return in_array($str, ['1', 'oui', 'yes', 'true', 'x', '✓']);
    }

    protected function cleanDecimal(mixed $value): ?float
    {
        if (is_null($value) || $value === '') return null;
        // Handle European decimal comma
        $str = str_replace([' ', "\u{00A0}"], '', (string) $value);
        $str = str_replace(',', '.', $str);
        return is_numeric($str) ? (float) $str : null;
    }
}
