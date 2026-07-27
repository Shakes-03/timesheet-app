<?php

namespace App\Filament\Resources\Timesheets\Pages;

use App\Filament\Resources\Timesheets\TimesheetResource;
use App\Models\Timesheet;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Runs before the edited record is saved to check for daily duplicate logs,
     * ignoring the record currently being edited.
     */
    protected function beforeSave(): void
    {
        $data = $this->data;

        // Check if another timesheet exists for this employee on this date,
        // excluding this current record ID
        $exists = Timesheet::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->where('id', '!=', $this->getRecord()->id)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Duplicate Entry Detected')
                ->body('Another timesheet entry for this employee on this specific date already exists.')
                ->danger()
                ->send();

            $this->halt(); 
        }
    }

    /**
     * Redirects to the index list after saving changes
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}