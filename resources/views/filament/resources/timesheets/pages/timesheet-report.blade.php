<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
    </x-filament-panels::form>
    
    <div class="text-sm text-gray-500">
        Select the payroll period above and click "Download Payroll Report" to generate the file.
    </div>
</x-filament-panels::page>