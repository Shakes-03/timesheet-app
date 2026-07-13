<?php

namespace App\Exports;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;

class PayrollExport extends ExcelExport
{
    protected string $name = 'table';

    public function setUp(): void
    {
        $this->withFilename('Payroll_Export_' . date('Y-m-d'))
            ->withColumns([
                Column::make('employee.first_name')->heading('First Name'),
                Column::make('employee.last_name')->heading('Last Name'),
                Column::make('employee.employee_number')->heading('Employee Number'),
                Column::make('employee.trade_occupation')->heading('Trade'),
                Column::make('normal_time_hours')->heading('NT'),
                Column::make('overtime_1_3_3_hours')->heading('1.33x'),
                Column::make('overtime_1_5_hours')->heading('1.5x'),
                Column::make('overtime_2_0_hours')->heading('2.0x'),
                Column::make('overtime_2_5_hours')->heading('2.5x'),
                Column::make('LOA_QTY')->heading('LOA QTY (R)'),
                Column::make('travelling_allowance')->heading('Travel (R)'),
                Column::make('notes')->heading('Completion Date'),
            ])
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->select([
                        'employee_id',
                        DB::raw('SUM(normal_time_hours) as normal_time_hours'),
                        DB::raw('SUM(overtime_1_3_3_hours) as overtime_1_3_3_hours'),
                        DB::raw('SUM(overtime_1_5_hours) as overtime_1_5_hours'),
                        DB::raw('SUM(overtime_2_0_hours) as overtime_2_0_hours'),
                        DB::raw('SUM(overtime_2_5_hours) as overtime_2_5_hours'),
                        DB::raw('SUM(LOA_QTY) as LOA_QTY'),
                        DB::raw('SUM(travelling_allowance) as travelling_allowance'),
                        DB::raw("GROUP_CONCAT(NULLIF(notes, ''), ', ') as notes"),
                    ])
                    ->groupBy('employee_id');
            });
    }

    public static function afterSheet(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $tableRange = "A1:{$highestColumn}{$highestRow}";

        $excelTable = new \PhpOffice\PhpSpreadsheet\Worksheet\Table($tableRange, 'PayrollTable');
        
        $tableStyle = new \PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle();
        $tableStyle->setTheme(\PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle::TABLE_STYLE_MEDIUM9);
        $excelTable->setStyle($tableStyle);
        
        $sheet->addTable($excelTable);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],
        ];
    }
}