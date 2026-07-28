<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class PayrollExport extends ExcelExport implements WithEvents
{
    public function setUp(): void
    {
        $this->withFilename('Payroll_Export_' . date('Y-m-d'))
            ->withColumns([
                Column::make('employee_number', 'Emp. No.'),
                Column::make('first_name', 'Employee Initial'),
                Column::make('trade_occupation', 'Trade'),
                Column::make('hourly_rate', 'Normal Rate per Hour'),
                Column::make('start_date', 'Start Date'),
                Column::make('basic_rate', 'Basic Rate'),
                Column::make('overtime_1_33', 'Overtime 1.33'),
                Column::make('overtime_1_5', 'Overtime 1.5'),
                Column::make('overtime_2_0', 'Overtime 2.0'),
                Column::make('overtime_2_5', 'Overtime 2.5'),
                Column::make('pph', 'Pph'),
                Column::make('night_normal', 'Night Normal'),
                Column::make('travelling_allowance', 'Travelling Allowance'),
                Column::make('backpay', 'Backpay'),
                Column::make('sick_leave_normal', 'Sick Leave Normal'),
                Column::make('eoc_date', 'EOC Date'),
                Column::make('ytd_normal_hours', 'YTD Normal Hours'),
                Column::make('shifts_worked', 'Shifts Worked'),
                Column::make('leave_pay', 'Leave Pay'),
                Column::make('leave_enhance', 'Leave Enhance'),
                Column::make('proc', 'Proc'),
                Column::make('gross_pay', 'Gross Pay'),
                Column::make('paye', 'PAYE'),
                Column::make('uif', 'UIF'),
                Column::make('provident_fund', 'Provident Fund'),
                Column::make('sick_pay_fund', 'Sick Pay Fund'),
                Column::make('dispute_levy', 'Dispute Levy'),
                Column::make('meibc_admin', 'MEIBC Admin'),
                Column::make('advance_loan', 'Advance/Loan'),
                Column::make('netto_salary', 'Netto Salary'),
                Column::make('sdl', 'SDL'),
                Column::make('uif_cc', 'UIF CC'),
                Column::make('dispute_levy_cc', 'Dispute Levy CC'),
                Column::make('meibc_admin_cc', 'MEIBC Admin CC'),
                Column::make('provident_fund_cc', 'Provident Fund CC'),
                Column::make('tech_fund_cc', 'Tech Fund CC'),
                Column::make('leave_provisions', 'Leave Provisions'),
                Column::make('leave_enhancement_provision', 'Leave Enhancement Provision'),
            ])
            ->modifyQueryUsing(function ($query) {
                $sub = DB::table('timesheets')
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
                       DB::raw("GROUP_CONCAT(NULLIF(notes, ''), ', ') as all_notes")
                   )
                   ->groupBy('employee_id');

                return $query
                    ->reorder()
                    ->fromSub($sub, 'timesheets') 
                    ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
                    ->join('trades', 'employees.trade_id', '=', 'trades.id')
                    ->select([
                        'employees.employee_number',
                        'employees.first_name',
                        'trades.description as trade_occupation',
                        'employees.hourly_rate',
                        DB::raw('COALESCE(employees.start_date, "") as start_date'),

                        // Separate Rate Component Breakdowns (Rounded) matching database aliases with underscores
                        DB::raw('ROUND((timesheets.total_nt * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END), 2) as basic_rate'),
                        DB::raw('ROUND((timesheets.total_ot133 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_1_33, 0) END), 2) as overtime_1_33'),
                        DB::raw('ROUND((timesheets.total_ot15 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_1_5, 0) END), 2) as overtime_1_5'),
                        DB::raw('ROUND((timesheets.total_ot20 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_2_0, 0) END), 2) as overtime_2_0'),
                        DB::raw('ROUND((timesheets.total_ot25 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_2_5, 0) END), 2) as overtime_2_5'),

                        // Secondary Allowances / Placeholders
                        DB::raw('0.00 as pph'),
                        DB::raw('0.00 as night_normal'),
                        DB::raw('ROUND(COALESCE(timesheets.total_travel, 0), 2) as travelling_allowance'),
                        DB::raw('0.00 as backpay'),
                        DB::raw('0.00 as sick_leave_normal'),
                        DB::raw('COALESCE(timesheets.all_notes, "") as eoc_date'),
                        DB::raw('0 as ytd_normal_hours'),

                        // Shifts Worked Calculation based on EOC check
                        DB::raw('ROUND(
                            CASE 
                                WHEN COALESCE(timesheets.all_notes, "") <> "" THEN 
                                    (
                                        (
                                            timesheets.total_nt + 
                                            (timesheets.total_nt * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END)
                                        ) 
                                        / 
                                        NULLIF(CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END, 0)
                                    ) / 8.5
                                ELSE 
                                    0 
                            END, 
                        2) as shifts_worked'),

                        // Leave Pay Calculation based on EOC check
                        DB::raw('ROUND(
                            CASE 
                                WHEN COALESCE(timesheets.all_notes, "") <> "" THEN 
                                    (
                                        (
                                            CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END 
                                            * 40 * 3 * 
                                            (
                                                (
                                                    (
                                                        timesheets.total_nt + 
                                                        (timesheets.total_nt * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END)
                                                    ) 
                                                    / 
                                                    NULLIF(CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END, 0)
                                                ) / 8.5
                                            )
                                        ) / 234
                                    )
                                ELSE 
                                    0.00 
                            END, 
                        2) as leave_pay'),

                        // Leave Enhancement Calculation based on EOC check
                        DB::raw('ROUND(
                            CASE 
                                WHEN COALESCE(timesheets.all_notes, "") <> "" THEN 
                                    (
                                        (
                                            CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END 
                                            * 40 * 52 * 0.0833 * 
                                            (
                                                (
                                                    (
                                                        timesheets.total_nt + 
                                                        (timesheets.total_nt * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END)
                                                    ) 
                                                    / 
                                                    NULLIF(CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END, 0)
                                                ) / 8.5
                                            )
                                        ) / 234
                                    )
                                ELSE 
                                    0.00 
                            END, 
                        2) as leave_enhance'),

                        DB::raw('"" as proc'),

                        // Base SQL Gross Pay Placeholder
                        DB::raw('0.00 as gross_pay'),

                        // Manual Tax Placeholders
                        DB::raw('0.00 as paye'),
                        DB::raw('0.00 as uif'),

                        // Trade Deductions (Rounded)
                        DB::raw('ROUND(((timesheets.total_nt * COALESCE(trades.provident_fund_emp, 0.0)) / 0.083) * 0.075, 2) as provident_fund'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.sick_pay_fund, 0)), 2) as sick_pay_fund'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.dispute_levy, 0)), 2) as dispute_levy'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.meibc_admin, 0)), 2) as meibc_admin'),
                        DB::raw('0.00 as advance_loan'),

                        // Netto Salary Placeholder
                        DB::raw('0.00 as netto_salary'),

                        // Company Contributions & Provisions (Rounded)
                        DB::raw('ROUND(COALESCE(trades.sdl_amount, 0), 2) as sdl'),
                        DB::raw('0.00 as uif_cc'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.dispute_levy_cc, 0)), 2) as dispute_levy_cc'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.meibc_admin_cc, 0)), 2) as meibc_admin_cc'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.provident_fund_cc, 0)), 2) as provident_fund_cc'),
                        DB::raw('ROUND(COALESCE(trades."Tech fund", 0), 2) as tech_fund_cc'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.leave_rate, 0)), 2) as leave_provisions'),
                        DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.enhancement_bonus, 0)), 2) as leave_enhancement_provision'),
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
                
                $dataRowsEnd = $highestRow;
                $totalRow = $highestRow + 1;

                $sumColumns = ['V', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL'];

                // Force numeric formatting on data rows for the primary tracked columns
                for ($row = 2; $row <= $dataRowsEnd; $row++) {
                    foreach ($sumColumns as $col) {
                        $cellVal = $sheet->getCell("{$col}{$row}")->getValue();
                        if ($cellVal !== null && $cellVal !== '') {
                            $sheet->setCellValueExplicit("{$col}{$row}", (float) $cellVal, DataType::TYPE_NUMERIC);
                        }
                    }

                    // Dynamic Gross Pay Formula per row
                    $sheet->setCellValue("V{$row}", "=F{$row}+G{$row}+H{$row}+I{$row}+J{$row}+K{$row}+L{$row}+M{$row}+N{$row}+O{$row}+S{$row}+T{$row}");

                    // Dynamic Netto Salary Formula per row
                    $sheet->setCellValue("AD{$row}", "=V{$row}-(W{$row}+X{$row}+Y{$row}+Z{$row}+AA{$row}+AB{$row}+AC{$row})");
                }

                // Include Netto Salary column ('AD') in the total sum columns array
                $sumColumns[] = 'AD';

                // Set Total row label and SUBTOTAL formulas (9 = SUM ignoring hidden rows from filters)
                $sheet->setCellValue("A{$totalRow}", "Total");
                
                foreach ($sumColumns as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUBTOTAL(9, {$col}2:{$col}{$dataRowsEnd})");
                }

                // Apply Table styling
                $tableRange = "A1:{$highestColumn}{$totalRow}";
                $excelTable = new Table($tableRange, 'PayrollTable');

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM9);
                $tableStyle->setShowRowStripes(true); 

                $excelTable->setStyle($tableStyle);
                $sheet->addTable($excelTable);

                // Row indices for the custom summary block below the table
                $ctcRow = $totalRow + 2;
                $ctcLeaveRow = $totalRow + 3;

                // Cost To Company (CTC) 
                $sheet->setCellValue("A{$ctcRow}", "Cost To Company (CTC)");
                $sheet->setCellValue("B{$ctcRow}", "=V{$totalRow}+AE{$totalRow}+AF{$totalRow}+AG{$totalRow}+AH{$totalRow}+AI{$totalRow}+AJ{$totalRow}");

                $sheet->setCellValue("A{$ctcLeaveRow}", "CTC + Leave Provision");
                $sheet->setCellValue("B{$ctcLeaveRow}", "=B{$ctcRow}+AK{$totalRow}+AL{$totalRow}");

                // --- ACCOUNTING NUMBER FORMATTING & ALIGNMENT ---
                $accountingFormat = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
                
                $financialColumns = array_merge(['D', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'], $sumColumns);

                foreach ($financialColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$totalRow}")
                          ->getNumberFormat()
                          ->setFormatCode($accountingFormat);
                }

                $sheet->getStyle("B{$ctcRow}:B{$ctcLeaveRow}")
                      ->getNumberFormat()
                      ->setFormatCode($accountingFormat);

                
                Calculation::getInstance($sheet->getParent())->disableCalculationCache();
            },
        ];
    }
}