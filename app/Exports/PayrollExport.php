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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport extends ExcelExport implements WithEvents
{
    protected ?string $clientPrefix;

    public function __construct(string $name = 'Payroll', ?string $clientPrefix = null)
    {
        parent::__construct($name);
        $this->clientPrefix = $clientPrefix;
    }

    public function setUp(): void
    {
        $prefix = $this->clientPrefix;

        $this->withFilename('Payroll_Export_' . ($prefix ? $prefix . '_' : '') . date('Y-m-d'))
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
            ->modifyQueryUsing(function ($query) use ($prefix) {
                // If Filament Excel passes a filtered table query, grab any active client filter from the request if not passed via constructor
                if (!$prefix && request()->has('tableFilters.client.value')) {
                    $prefix = request()->input('tableFilters.client.value');
                    $this->clientPrefix = $prefix;
                }

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

                $mainQuery = $query
                    ->reorder()
                    ->fromSub($sub, 'timesheets') 
                    ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
                    ->join('trades', 'employees.trade_id', '=', 'trades.id');

                if ($prefix) {
                    $mainQuery->where('employees.employee_number', 'like', $prefix . '%');
                }

                return $mainQuery->select([
                    'employees.employee_number',
                    'employees.first_name',
                    'trades.description as trade_occupation',
                    'employees.hourly_rate',
                    DB::raw('COALESCE(employees.start_date, "") as start_date'),

                    DB::raw('ROUND((timesheets.total_nt * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.normal_rate_to_man, 0) ELSE COALESCE(employees.hourly_rate, 0) END), 2) as basic_rate'),
                    DB::raw('ROUND((timesheets.total_ot133 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_1_33, 0) END), 2) as overtime_1_33'),
                    DB::raw('ROUND((timesheets.total_ot15 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_1_5, 0) END), 2) as overtime_1_5'),
                    DB::raw('ROUND((timesheets.total_ot20 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_2_0, 0) END), 2) as overtime_2_0'),
                    DB::raw('ROUND((timesheets.total_ot25 * CASE WHEN trades.rate_type = "flat" THEN COALESCE(trades.flat_overtime_override, 0) ELSE COALESCE(trades.rate_2_5, 0) END), 2) as overtime_2_5'),

                    DB::raw('0.00 as pph'),
                    DB::raw('0.00 as night_normal'),
                    DB::raw('ROUND(COALESCE(timesheets.total_travel, 0), 2) as travelling_allowance'),
                    DB::raw('0.00 as backpay'),
                    DB::raw('0.00 as sick_leave_normal'),
                    DB::raw('COALESCE(timesheets.all_notes, "") as eoc_date'),
                    DB::raw('0 as ytd_normal_hours'),

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
                    DB::raw('0.00 as gross_pay'),
                    DB::raw('0.00 as paye'),
                    DB::raw('0.00 as uif'),

                    DB::raw('ROUND(((timesheets.total_nt * COALESCE(trades.provident_fund_emp, 0.0)) / 0.083) * 0.075, 2) as provident_fund'),
                    DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.sick_pay_fund, 0)), 2) as sick_pay_fund'),
                    DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.dispute_levy, 0)), 2) as dispute_levy'),
                    DB::raw('ROUND((timesheets.total_nt * COALESCE(trades.meibc_admin, 0)), 2) as meibc_admin'),
                    DB::raw('0.00 as advance_loan'),
                    DB::raw('0.00 as netto_salary'),

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
                $mainSheetTitle = $sheet->getTitle();
                
                $dataRowsEnd = $highestRow;
                $totalRow = $highestRow + 1;

                $sumColumns = ['V', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL'];

                for ($row = 2; $row <= $dataRowsEnd; $row++) {
                    foreach ($sumColumns as $col) {
                        $cellVal = $sheet->getCell("{$col}{$row}")->getValue();
                        if ($cellVal !== null && $cellVal !== '') {
                            $sheet->setCellValueExplicit("{$col}{$row}", (float) $cellVal, DataType::TYPE_NUMERIC);
                        }
                    }

                    $sheet->setCellValue("V{$row}", "=F{$row}+G{$row}+H{$row}+I{$row}+J{$row}+K{$row}+L{$row}+M{$row}+N{$row}+O{$row}+S{$row}+T{$row}");
                    $sheet->setCellValue("AD{$row}", "=V{$row}-(W{$row}+X{$row}+Y{$row}+Z{$row}+AA{$row}+AB{$row}+AC{$row})");
                }

                $sumColumns[] = 'AD';

                $sheet->setCellValue("A{$totalRow}", "Total");
                
                foreach ($sumColumns as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUBTOTAL(9, {$col}2:{$col}{$dataRowsEnd})");
                }

                $tableRange = "A1:{$highestColumn}{$totalRow}";
                $excelTable = new Table($tableRange, 'PayrollTable');

                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM9);
                $tableStyle->setShowRowStripes(true); 

                $excelTable->setStyle($tableStyle);
                $sheet->addTable($excelTable);

                $ctcRow = $totalRow + 2;
                $ctcLeaveRow = $totalRow + 3;

                $sheet->setCellValue("A{$ctcRow}", "Cost To Company (CTC)");
                $sheet->setCellValue("B{$ctcRow}", "=V{$totalRow}+AE{$totalRow}+AF{$totalRow}+AG{$totalRow}+AH{$totalRow}+AI{$totalRow}+AJ{$totalRow}");

                $sheet->setCellValue("A{$ctcLeaveRow}", "CTC + Leave Provision");
                $sheet->setCellValue("B{$ctcLeaveRow}", "=B{$ctcRow}+AK{$totalRow}+AL{$totalRow}");

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

                // --- SHEET 2: Raw Clockfile Data Sheet (Using exact matching subquery) ---
                $spreadsheet = $sheet->getParent();
                $rawSheet = new Worksheet($spreadsheet, 'Clockfile Raw Data');
                $spreadsheet->addSheet($rawSheet, 1);

                $subRaw = DB::table('timesheets')
                   ->select(
                       DB::raw('MIN(id) as id'), 
                       'employee_id',
                       DB::raw('SUM(COALESCE(normal_time_hours, 0)) as total_nt'),
                       DB::raw('SUM(COALESCE(overtime_1_3_3_hours, 0)) as total_ot133'),
                       DB::raw('SUM(COALESCE(overtime_1_5_hours, 0)) as total_ot15'),
                       DB::raw('SUM(COALESCE(overtime_2_0_hours, 0)) as total_ot20'),
                       DB::raw('SUM(COALESCE(overtime_2_5_hours, 0)) as total_ot25'),
                       DB::raw('SUM(COALESCE(travelling_allowance, 0)) as total_travel')
                   )
                   ->groupBy('employee_id');

                $rawQuery = DB::table('timesheets')
                    ->reorder()
                    ->fromSub($subRaw, 'timesheets')
                    ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
                    ->leftJoin('trades', 'employees.trade_id', '=', 'trades.id');

                if ($this->clientPrefix) {
                    $rawQuery->where('employees.employee_number', 'like', $this->clientPrefix . '%');
                }

                $rawClockData = $rawQuery->select(
                        'employees.employee_number',
                        'employees.first_name',
                        'employees.last_name as surname',
                        'timesheets.total_nt as normal_hours',
                        'timesheets.total_ot133 as ot_133',
                        'timesheets.total_ot15 as ot_15',
                        'timesheets.total_ot20 as ot_20',
                        'timesheets.total_ot25 as ot_25',
                        'timesheets.total_travel as travel_allowance'
                    )->get();

                $rawSheet->setCellValue("A1", "Emp. No.");
                $rawSheet->setCellValue("B1", "First Name");
                $rawSheet->setCellValue("C1", "Surname");
                $rawSheet->setCellValue("D1", "Basic Rate");
                $rawSheet->setCellValue("E1", "Overtime 1.33");
                $rawSheet->setCellValue("F1", "Overtime 1.5");
                $rawSheet->setCellValue("G1", "Flat Overtime 1.5");
                $rawSheet->setCellValue("H1", "Overtime 2");
                $rawSheet->setCellValue("I1", "Overtime 2.5");
                $rawSheet->setCellValue("J1", "PPH");
                $rawSheet->setCellValue("K1", "Night - Normal");
                $rawSheet->setCellValue("L1", "Travel Allowance");
                $rawSheet->setCellValue("M1", "Provident Fund");
                $rawSheet->setCellValue("N1", "Sick Leave");
                $rawSheet->setCellValue("O1", "Sick Pay Fund");
                $rawSheet->setCellValue("P1", "Dispute Levy");
                $rawSheet->setCellValue("Q1", "MEIBC Admin");
                $rawSheet->setCellValue("R1", "Dispute Levy CC");
                $rawSheet->setCellValue("S1", "MEIBC Admin CC");
                $rawSheet->setCellValue("T1", "Tech Fund CC");
                $rawSheet->setCellValue("U1", "Advance");

                $rawRowIdx = 2;
                foreach ($rawClockData as $entry) {
                    $rawSheet->setCellValue("A{$rawRowIdx}", $entry->employee_number);
                    $rawSheet->setCellValue("B{$rawRowIdx}", $entry->first_name);
                    $rawSheet->setCellValue("C{$rawRowIdx}", $entry->surname ?? '');
                    $rawSheet->setCellValue("D{$rawRowIdx}", (float) $entry->normal_hours);
                    $rawSheet->setCellValue("E{$rawRowIdx}", (float) $entry->ot_133);
                    $rawSheet->setCellValue("F{$rawRowIdx}", (float) $entry->ot_15);
                    $rawSheet->setCellValue("G{$rawRowIdx}", 0);
                    $rawSheet->setCellValue("H{$rawRowIdx}", (float) $entry->ot_20);
                    $rawSheet->setCellValue("I{$rawRowIdx}", (float) $entry->ot_25);
                    $rawSheet->setCellValue("J{$rawRowIdx}", 0);
                    $rawSheet->setCellValue("K{$rawRowIdx}", 0);
                    $rawSheet->setCellValue("L{$rawRowIdx}", (float) $entry->travel_allowance);
                    
                    // Maps exact matching row index to Sheet 1 calculation columns
                    $rawSheet->setCellValue("M{$rawRowIdx}", "='{$mainSheetTitle}'!Y{$rawRowIdx}");
                    $rawSheet->setCellValue("N{$rawRowIdx}", "='{$mainSheetTitle}'!AA{$rawRowIdx}");
                    $rawSheet->setCellValue("O{$rawRowIdx}", "='{$mainSheetTitle}'!AA{$rawRowIdx}");
                    $rawSheet->setCellValue("P{$rawRowIdx}", "='{$mainSheetTitle}'!AB{$rawRowIdx}");
                    $rawSheet->setCellValue("Q{$rawRowIdx}", "='{$mainSheetTitle}'!AC{$rawRowIdx}");
                    $rawSheet->setCellValue("R{$rawRowIdx}", "='{$mainSheetTitle}'!AE{$rawRowIdx}");
                    $rawSheet->setCellValue("S{$rawRowIdx}", "='{$mainSheetTitle}'!AF{$rawRowIdx}");
                    $rawSheet->setCellValue("T{$rawRowIdx}", "='{$mainSheetTitle}'!AG{$rawRowIdx}");
                    $rawSheet->setCellValue("U{$rawRowIdx}", 0);
                    
                    $rawRowIdx++;
                }

                foreach (range('A', 'U') as $columnID) {
                    $rawSheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                Calculation::getInstance($sheet->getParent())->disableCalculationCache();
            },
        ];
    }
}