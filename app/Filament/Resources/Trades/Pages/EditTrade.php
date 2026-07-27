<?php

namespace App\Filament\Resources\Trades\Pages;

use App\Filament\Resources\Trades\TradeResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditTrade extends EditRecord
{
    protected static string $resource = TradeResource::class;

    public function form(Schema $schema): Schema
    {
        return TradeResource::form($schema);
    }
}
