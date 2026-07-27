<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Trade;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Details')
                    ->schema([
                        TextInput::make('first_name')
                            ->required(),
                            
                        TextInput::make('last_name')
                            ->required(),
                            
                        TextInput::make('employee_number')
                            ->label('EMP Code')
                            ->required()
                            ->unique(table: 'employees', column: 'employee_number', ignoreRecord: true) //o that when you update an existing employee it doesn't trigger a duplicate validation error
                            ->validationMessages([
                                'unique' => 'This EMP Code has already been assigned to another staff member.',
                            ]),
                            
                        Select::make('trade_id')
                            ->relationship(
                                name: 'trade', 
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereNotNull('name')
                            )
                            ->label('Trade / Role')
                            ->placeholder('Select a master trade...')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
    ->afterStateUpdated(function (Set $set, ?string $state) {
        $trade = \App\Models\Trade::find($state);
        if ($trade) {
            $set('hourly_rate', $trade->normal_rate_to_man);
        }
    }),

                        TextInput::make('id_number')
                            ->label('ID Number')
                            ->length(13)
                            ->regex('/^[0-9]{13}$/')
                            ->placeholder('e.g., 9403065745082')
                            ->required()
                            ->unique(table: 'employees', column: 'id_number', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'An employee with this ID Number is already registered in the system.',
                            ]),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required(),
                    ])->columns(2), 

                Section::make('Payroll Configuration')
                    ->schema([
                        TextInput::make('hourly_rate')
                            ->label('Hourly Rate')
                            ->numeric()
                            ->prefix('R')
                            ->placeholder('0.00')
                            ->required()
                            ->reactive(), // Make dynamic updates depending on the selected trade
                    ]), 
            ]);
    }
}