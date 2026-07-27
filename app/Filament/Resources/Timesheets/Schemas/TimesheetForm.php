<?php

namespace App\Filament\Resources\Timesheets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class TimesheetForm
{
    public static function configure(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
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
                            ->required()
                            ->rules([
                                fn (\Filament\Schemas\Components\Utilities\Get $get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $employeeId = $get('employee_id');
                                    
                                    if (! $employeeId || ! $value) {
                                        return;
                                    }
                                    $formattedDate = \Carbon\Carbon::parse($value)->format('Y-m-d');

                                    $query = \DB::table('timesheets')
                                        ->where('employee_id', $employeeId)
                                        ->whereDate('date', $formattedDate);

                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }

                                    if ($query->exists()) {
                                        $fail("The selected employee already has a timesheet entry for this date."); //prevents duplicates
                                    }
                                },
                            ])
                         ->validationAttribute('date for this employee'),
                  ]),

                
                Section::make('Standard Shifts')
                    ->compact()
                    ->columns(5)
                    ->schema([
                        TextInput::make('normal_time_hours')
                            ->label('Normal Time (NT)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                            ->live(),

                        TextInput::make('overtime_1_3_3_hours')
                            ->label('Overtime (1.33x)')
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

                        TextInput::make('overtime_2_0_hours')
                            ->label('Overtime (2.0x)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),

                            TextInput::make('overtime_2_5_hours')
                            ->label('Overtime (2.5x)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(),
                    ]),

                
                Section::make('LOA & Travel Allowances')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        TextInput::make('LOA_QTY')
                            ->label('LOA Quantity (R)')
                            ->numeric()
                            ->prefix('R')
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

                
                Grid::make(1)
                    ->schema([
                        Placeholder::make('total_hours')
                            ->label('Total Hours Accumulated For This Day')
                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $nt = (float) ($get('normal_time_hours') ?? 0);
                                $ot15 = (float) ($get('overtime_1_3_3_hours') ?? 0);
                                $sat = (float) ($get('overtime_1_5_hours') ?? 0);
                                $sun20 = (float) ($get('overtime_2_0_hours') ?? 0);
                                $pphnt = (float) ($get('overtime_2_5_hours') ?? 0);
                                $pphot133 = (float) ($get('LOA_QTY') ?? 0);
                                $pphot25 = (float) ($get('travelling_allowance') ?? 0);

                                $total = $nt + $ot15 + $sat + $sun20 + $pphnt; //so that she can see the hours for the day and didnt add a zero at the end of a number making the hours wrong

                                return number_format($total, 2) . ' hours';
                            }),

                        Textarea::make('notes')
                            ->label('Shift Notes Completion Reasons') //for EOC dates or any other notes
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}