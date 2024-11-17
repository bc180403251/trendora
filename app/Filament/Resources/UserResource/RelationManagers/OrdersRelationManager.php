<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Forms;
// use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 
 
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order Id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('PKR'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state):string => match ($state) {
                        'new'=>'info',
                        'processing'=>'warning',
                        'shipped'=>'warining',
                        'deleivered'=>'success',
                        'cancelled'=>'danger'
                    })
                    ->icon(fn(string $state):string => match ($state) {
                        'new'=>'heroicon-o-shopping-bag',
                        'processing'=> 'heroicon-m-arrow-path',
                        'shipped'=>'heroicon-m-truck',
                        'delivered'=> 'heroicon-o-check-circle',
                        'cancelled'=> 'heroicon-o-x-circle',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('View Order')
                    ->url(fn (Order $record):string => OrderResource::getUrl('view',['record'=> $record]))
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
