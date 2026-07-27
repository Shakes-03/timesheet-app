<?php

namespace App\Filament\Resources\Timesheets\Pages;

use App\Filament\Resources\Timesheets\TimesheetResource;
use App\Models\Timesheet;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    /**
     * Runs before the record is saved to check for daily duplicate logs
     */
    protected function beforeCreate(): void
    {
        $data = $this->data;

        $exists = Timesheet::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Duplicate Entry Detected')
                ->body('A timesheet entry for this employee on this specific date already exists. Please find the existing record in the list and edit it instead.')
                ->danger()
                ->send();

            $this->halt(); 
        }
    }

    /**
     * Redirects to the index list after saving
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}