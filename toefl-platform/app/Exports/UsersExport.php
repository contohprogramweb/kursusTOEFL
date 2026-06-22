<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $query = User::query();

        // Apply filters
        if (isset($this->filters['role']) && $this->filters['role']) {
            $query->where('role', $this->filters['role']);
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['search']) && $this->filters['search']) {
            $query->search($this->filters['search']);
        }

        if (isset($this->filters['start_date']) || isset($this->filters['end_date'])) {
            $query->dateRange(
                $this->filters['start_date'] ?? null,
                $this->filters['end_date'] ?? null
            );
        }

        // Apply sorting
        $sortField = $this->filters['sort_field'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Full Name',
            'Email',
            'Role',
            'Status',
            'Email Verified',
            'Created At',
            'Suspended At',
            'Suspension Reason',
            'Suspended By',
        ];
    }

    /**
     * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->full_name,
            $user->email,
            ucfirst($user->role),
            ucfirst($user->status),
            $user->email_verified ? 'Yes' : 'No',
            $user->created_at->format('Y-m-d H:i:s'),
            $user->suspended_at ? $user->suspended_at->format('Y-m-d H:i:s') : '',
            $user->suspension_reason ?? '',
            $user->suspendedByUser ? $user->suspendedByUser->full_name : '',
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
