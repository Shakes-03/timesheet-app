<?php

namespace App\Filament\Resources\Trades;

use App\Filament\Resources\Trades\Schemas\TradeForm;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Resources\Trades\Pages;
use App\Models\Trade;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; 
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class TradeResource extends Resource
{
    public static function getNavigationIcon(): string
{
    return 'heroicon-o-briefcase';
}
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Trade Details')->schema([
                TextInput::make('description')
                    ->placeholder('e.g., Boilermaker, Planner'),
                Select::make('rate_type')
                    ->required()
                    ->options([
                        'industrial' => 'Industrial', //not sure if these works but its still useful so that she knows which rates are falt maybe
                        'flat' => 'Flat Rate',
                    ])
                    ->reactive()
                    ->default('industrial'), //most are industrial
                TextInput::make('normal_rate_to_man')
                    ->label('Normal Rate to Man (R)')
                    ->numeric()
                    ->required()
                    ->prefix('R'),
                TextInput::make('flat_overtime_override')
                    ->label('Fixed Overtime Rate (R)')
                    ->numeric()
                    ->prefix('R')
                    ->visible(fn ($get) => $get('rate_type') === 'flat')
                    ->required(fn ($get) => $get('rate_type') === 'flat'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('rate_type')->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('normal_rate_to_man')->label('Normal Rate (R)')->money('ZAR'),
                TextColumn::make('flat_overtime_override')->label('Fixed Overtime (R)')->money('ZAR'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrades::route('/'),
            'create' => Pages\CreateTrade::route('/create'),
            'edit' => Pages\EditTrade::route('/{record}/edit'),
        ];
    }
}