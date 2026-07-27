<?php

namespace App\Filament\Resources\Trades\Pages;

use App\Filament\Resources\Trades\TradeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateTrade extends CreateRecord
{
    protected static string $resource = TradeResource::class;

    // Force the page to use the Schema builder
    public function form(Schema $schema): Schema
    {
        return TradeResource::form($schema);
    }
}
