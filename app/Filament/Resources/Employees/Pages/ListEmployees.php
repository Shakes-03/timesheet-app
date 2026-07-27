<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;  
use Filament\Actions\CreateAction; // Import this
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class; 

    // Add this method to force the Create button to appear
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}