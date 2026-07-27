<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

class PayrollExport extends ExcelExport implements WithEvents
{
    public function setUp(): void
    {
        $this->withFilename('Payroll_Export_' . date('Y-m-d')) //not sure if we want to rename it to include the date or not
            ->withColumns([
                Column::make('first_name', 'First Name'),
                Column::make('last_name', 'Last Name'),
                Column::make('employee_number', 'Employee Number'),
                Column::make('trade_occupation', 'Trade'),
                Column::make('normal_time_hours', 'NT'),
                Column::make('overtime_1_3_3_hours', '1.33x'),
                Column::make('overtime_1_5_hours', '1.5x'),
                Column::make('overtime_2_0_hours', '2.0x'),
                Column::make('overtime_2_5_hours', '2.5x'),
                Column::make('LOA_QTY', 'LOA QTY'),
                Column::make('travelling_allowance', 'Travel (R)'),
                Column::make('total_owed', 'Total Owed (Payroll)'),
                Column::make('notes', 'Completion Date'),
            ])
            ->modifyQueryUsing(function ($query) {
                $sub = DB::table('timesheets')//gives us the total hours for each employee
                 ->select(
                     DB::raw('MIN(id) as id'), 
                     'employee_id',
                     DB::raw('SUM(COALESCE(normal_time_hours, 0)) as total_nt'),
                     DB::raw('SUM(COALESCE(overtime_1_3_3_hours, 0)) as total_ot133'),
                     DB::raw('SUM(COALESCE(overtime_1_5_hours, 0)) as total_ot15'),
                     DB::raw('SUM(COALESCE(overtime_2_0_hours, 0)) as total_ot20'),
                     DB::raw('SUM(COALESCE(overtime_2_5_hours, 0)) as total_ot25'),
                     DB::raw('SUM(COALESCE(LOA_QTY, 0)) as total_loa'),
                     DB::raw('SUM(COALESCE(travelling_allowance, 0)) as total_travel'),
                     DB::raw("GROUP_CONCAT(NULLIF(notes, ''), ', ') as all_notes")//i made it group concat because in case she does multiple EOC and i dont know if it would crash
              )
              ->groupBy('employee_id');//so that we get only one line per employees
          return $query
              ->reorder()
              ->fromSub($sub, 'timesheets') 
              ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
              ->join('trades', 'employees.trade_id', '=', 'trades.id')//links trades and corresponding worker for the rates
              ->select([
                  'employees.first_name',
                  'employees.last_name',
                  'employees.employee_number',
                  'employees.hourly_rate',
                  'trades.description as trade_occupation',
                  'timesheets.total_nt as normal_time_hours',
                  'timesheets.total_ot133 as overtime_1_3_3_hours',
                  'timesheets.total_ot15 as overtime_1_5_hours',
                  'timesheets.total_ot20 as overtime_2_0_hours',
                  'timesheets.total_ot25 as overtime_2_5_hours',
                  'timesheets.total_loa as LOA_QTY',
                  'timesheets.total_travel as travelling_allowance',
                  DB::raw('(
                    (
                          CASE 
                             WHEN trades.rate_type = "flat" THEN 
                                  (timesheets.total_nt * COALESCE(trades.normal_rate_to_man, 0)) + 
                                  ((timesheets.total_ot133 + timesheets.total_ot15 + timesheets.total_ot20 + timesheets.total_ot25) * COALESCE(trades.flat_overtime_override, 0)) 
                             ELSE 
                                 (timesheets.total_nt * COALESCE(employees.hourly_rate, 0)) + 
                                 (timesheets.total_ot133 * COALESCE(trades.rate_1_33, 0)) + 
                                 (timesheets.total_ot15 * COALESCE(trades.rate_1_5, 0)) + 
                                 (timesheets.total_ot20 * COALESCE(trades.rate_2_0, 0)) + 
                                 (timesheets.total_ot25 * COALESCE(trades.rate_2_5, 0))
                     END 
                    + COALESCE(timesheets.total_loa, 0) 
                    + COALESCE(timesheets.total_travel, 0)
                   ) - COALESCE(trades.admin_fee, 0)
               ) as total_owed'), //calculations
               'timesheets.all_notes as notes'
             ]);
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $tableRange = "A1:{$highestColumn}{$highestRow}";

                $excelTable = new Table($tableRange, 'PayrollTable');
                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM9);
                $excelTable->setStyle($tableStyle);
                $sheet->addTable($excelTable);
            },
        ];
    }
}