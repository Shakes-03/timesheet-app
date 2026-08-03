<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use Illuminate\Support\Facades\DB;

class EmployeeHoursSummarySheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    public function query()
    {
        return DB::table('timesheets')
            ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
            ->select(
                'employees.employee_number',
                DB::raw('SUM(COALESCE(normal_time_hours, 0)) as total_nt'),
                DB::raw('SUM(COALESCE(overtime_1_3_3_hours, 0)) as total_ot133'),
                DB::raw('SUM(COALESCE(overtime_1_5_hours, 0)) as total_ot15'),
                DB::raw('SUM(COALESCE(overtime_2_0_hours, 0)) as total_ot20'),
                DB::raw('SUM(COALESCE(overtime_2_5_hours, 0)) as total_ot25')
            )
            ->groupBy('employees.employee_number', 'employees.id');
    }

    public function headings(): array
    {
        return [
            'Emp Code',
            'Total NT Hours',
            'Total Overtime 1.33',
            'Total Overtime 1.5',
            'Total Overtime 2.0',
            'Total Overtime 2.5',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_number,
            (float) $row->total_nt,
            (float) $row->total_ot133,
            (float) $row->total_ot15,
            (float) $row->total_ot20,
            (float) $row->total_ot25,
        ];
    }

    public function title(): string
    {
        return 'Hours Summary';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $dataRowsEnd = $highestRow;
                $totalRow = $highestRow + 1;
                $sumColumns = ['B', 'C', 'D', 'E', 'F'];

                $sheet->setCellValue("A{$totalRow}", "Total");
                
                foreach ($sumColumns as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUBTOTAL(9, {$col}2:{$col}{$dataRowsEnd})");
                }

                $tableRange = "A1:{$highestColumn}{$totalRow}";
                $excelTable = new Table($tableRange, 'HoursSummaryTable');

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM9);
                $tableStyle->setShowRowStripes(true); 

                $excelTable->setStyle($tableStyle);
                $sheet->addTable($excelTable);

                $hoursFormat = '#,##0.00';
                foreach ($sumColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$totalRow}")
                          ->getNumberFormat()
                          ->setFormatCode($hoursFormat);
                }
            },
        ];
    }
}