<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                    
                TextInput::make('last_name')
                    ->required(),
                    
                TextInput::make('employee_number')
                    ->label('EMP Code')
                    ->required(),
                    
                TextInput::make('trade_occupation')
                    ->label('Trade Occupation')
                    ->placeholder('e.g., BOILERMAKER')
                    ->required(),

                TextInput::make('id_number')
                    ->label('ID Number')
                    ->length(13)
                    ->regex('/^[0-9]{13}$/')
                    ->placeholder('e.g., 9403065745082')
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),
            ]);
    }
}