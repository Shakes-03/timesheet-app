<?php

namespace App\Filament\Resources\Timesheets\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use App\Exports\PayrollExport;

class TimesheetsTable
{
    public static function configure(Table $table): Table
    {
        Cache::forget('laravel-cache-filament-excel:exports:1');

        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => "{$record->employee?->first_name} {$record->employee?->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                    
                TextColumn::make('employee.employee_number')
                    ->label('Employee Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.trade.name')
                    ->label('Trade')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('normal_time_hours')
                    ->label('NT')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('overtime_1_3_3_hours')
                    ->label('1.33x')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('overtime_1_5_hours')
                    ->label('1.5x')
                    ->numeric()
                    ->sortable(), 

                TextColumn::make('overtime_2_0_hours')
                    ->label('2.0x')
                    ->numeric()
                    ->sortable(), 

                TextColumn::make('overtime_2_5_hours')
                    ->label('2.5x')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('LOA_QTY')
                    ->label('LOA QTY')
                    ->money('ZAR')
                    ->sortable(),

                TextColumn::make('travelling_allowance')
                    ->label('Travel (R)')
                    ->money('ZAR')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Shift Notes')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Filter by Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload(),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    })
            ])
            ->actions([
                
            ])
            ->bulkActions([
                ExportBulkAction::make('export_payroll')
                    ->label('Export Bi-Weekly Payroll')
                    ->exports([
                        PayrollExport::make(),
                    ]),
            ]);
    }
}