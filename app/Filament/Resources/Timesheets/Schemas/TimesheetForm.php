<?php

namespace App\Filament\Resources\Timesheets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class TimesheetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section 1: Header Assignment
                Section::make('Assignment')
                    ->description('Select the worker and the specific calendar day for this log entry.')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->relationship(name: 'employee', titleAttribute: 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->employee_number})")
                            ->searchable(['first_name', 'last_name', 'employee_number'])
                            ->preload()
                            ->required()
                            ->label('Employee'),

                        DatePicker::make('date')
                            ->label('Date Worked')
                            ->native(false)
                            ->displayFormat('l, d F Y')
                            ->closeOnDateSelect()
                            ->required(),
                    ]),

                // Section 2: Core Shift Hours
                Section::make('Standard & Weekend Shifts')
                    ->compact()
                    ->columns(4)
                    ->schema([
                        TextInput::make('normal_time_hours')
                            ->label('Normal Time (NT)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('overtime_1_5_hours')
                            ->label('Overtime (1.5x)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('saturday_hours')
                            ->label('Saturday (SAT)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('sunday_2_0_hours')
                            ->label('Sunday (SUN-2.0)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),
                    ]),

                // Section 3: Public Holidays & Allowances
                Section::make('Public Holidays & Travel Allowances')
                    ->compact()
                    ->columns(4)
                    ->schema([
                        TextInput::make('pph_normal_time_hours')
                            ->label('PPH Normal Time')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('pph_overtime_1_33_hours')
                            ->label('PPH Overtime (1.33x)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('pph_overtime_2_5_hours')
                            ->label('PPH Overtime (2.5x)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                        TextInput::make('travelling_allowance')
                            ->label('Travel Allowance (R)')
                            ->numeric()
                            ->prefix('R')
                            ->default(0)
                            ->minValue(0),
                    ]),

                // Section 4: Rolling Summary & Dynamic Calculation
                Grid::make(1)
                    ->schema([
                        Placeholder::make('total_hours')
                            ->label('Total Hours Accumulated For This Day')
                            ->content(function (Get $get) {
                                $nt = (float) ($get('normal_time_hours') ?? 0);
                                $ot15 = (float) ($get('overtime_1_5_hours') ?? 0);
                                $sat = (float) ($get('saturday_hours') ?? 0);
                                $sun20 = (float) ($get('sunday_2_0_hours') ?? 0);
                                $pphnt = (float) ($get('pph_normal_time_hours') ?? 0);
                                $pphot133 = (float) ($get('pph_overtime_1_33_hours') ?? 0);
                                $pphot25 = (float) ($get('pph_overtime_2_5_hours') ?? 0);

                                $total = $nt + $ot15 + $sat + $sun20 + $pphnt + $pphot133 + $pphot25;

                                return number_format($total, 2) . ' hours';
                            }),

                        Textarea::make('notes')
                            ->label('Shift Notes / Project Details / Completion Reasons')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}