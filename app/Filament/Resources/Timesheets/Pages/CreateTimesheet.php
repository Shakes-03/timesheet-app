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
      *Runs before the record is saved to check for duplicates
    
     */
    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $exists = Timesheet::where('employee_id', $data['employee_id'])
            ->where('week_ending_date', $data['week_ending_date'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Duplicate Entry Detected')
                ->body('An entry for this employee and week already exists. Please find the record in the list and use the "Edit" button instead.')
                ->danger()
                ->send();

            $this->halt(); 
        }
    }

    /**
     * Redirects
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}