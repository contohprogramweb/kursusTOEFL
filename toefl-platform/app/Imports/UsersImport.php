<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $previewRows = [];
    protected $errors = [];

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // This will be called during import commit
        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    /**
     * Process a single row
     */
    protected function processRow(array $row)
    {
        // Import logic will be handled in controller for better control
    }

    /**
     * Get preview rows (first 10)
     */
    public function getPreview(array $data): array
    {
        $preview = [];
        $count = 0;

        // Skip heading row and get first 10 data rows
        foreach ($data as $index => $row) {
            if ($index === 0) continue; // Skip header
            if ($count >= 10) break;

            $preview[] = [
                'row_number' => $index + 1,
                'full_name' => $row['full_name'] ?? '',
                'email' => $row['email'] ?? '',
                'role' => $row['role'] ?? '',
                'status' => $row['status'] ?? 'active',
            ];
            $count++;
        }

        return $preview;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            '*.full_name' => ['required', 'string', 'max:255'],
            '*.email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(null)],
            '*.role' => ['required', Rule::in(['student', 'instructor', 'admin', 'parent'])],
            '*.status' => ['nullable', Rule::in(['active', 'pending', 'suspended'])],
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages(): array
    {
        return [
            '*.full_name.required' => 'Full name is required for row :row',
            '*.email.required' => 'Email is required for row :row',
            '*.email.email' => 'Invalid email format for row :row',
            '*.email.unique' => 'Email already exists for row :row',
            '*.role.required' => 'Role is required for row :row',
            '*.role.in' => 'Invalid role for row :row. Must be student, instructor, admin, or parent',
        ];
    }

    /**
     * Custom validation attributes
     */
    public function customValidationAttributes(): array
    {
        return [
            '*.full_name' => 'full name',
            '*.email' => 'email',
            '*.role' => 'role',
            '*.status' => 'status',
        ];
    }
}
