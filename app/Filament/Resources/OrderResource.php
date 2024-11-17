<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Faker\Provider\ar_EG\Text;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Information')->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user','name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('payment_method')
                            ->options([
                                'stripe'=> 'Stripe',
                                'cod'=> 'Cash on Delivery'
                            ])
                            ->required(),
                        
                        Select::make('payment_status')
                                ->default('pending')
                                ->options([
                                    'pending'=>'Pending',
                                    'paid'=>'Paid',
                                    'failed'=>'Failed',
                                ])
                                ->required(),
                        ToggleButtons::make('status')
                            ->default('new')
                            ->inline()
                            ->options([
                                'new'=>'New',
                                'processing'=> 'Processing',
                                'shipped'=>'Shipped',
                                'delivered'=> 'Delivered',
                                'cancelled'=> 'Cancelled',
                            ])
                            ->colors([
                                'new'=>'info',
                                'processing'=> 'warning',
                                'shipped'=>'warning',
                                'delivered'=> 'success',
                                'cancelled'=> 'danger',

                            ])
                            ->icons([
                                'new'=>'heroicon-o-shopping-bag',
                                'processing'=> 'heroicon-m-arrow-path',
                                'shipped'=>'heroicon-m-truck',
                                'delivered'=> 'heroicon-o-check-circle',
                                'cancelled'=> 'heroicon-o-x-circle',

                            ]),

                        Select::make('currency')
                            ->options([
                                'pkr'=> 'PKR',
                                'usd'=> 'USD',
                                'eur'=> 'EUR',
                            ])
                            ->default('pkr')
                            ->required(),
                        Select::make('shipping_method')
                            ->options([
                                'sea_freight'=>'Sea Freight',
                                'air_freight'=>'Air Freight',
                                'dhl'=>'DHL',
                                'port_qasim'=>'Port Qasim',
                            ]),
                        Textarea::make('notes')
                            ->columnSpanFull(),

                    
                    ])->columns(2),
                    Section::make('Order Items')->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product','name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, Set $set) => $set('unit_amount',Product::find($state)?->price ?? 0))
                                    ->afterStateUpdated(fn($state, Set $set) => $set('total_amount',Product::find($state)?->price ?? 0))
                                    ->columnSpan(4),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_amount', $get('quantity') * $get('unit_amount')))
                                    ->columnSpan(2),

                                TextInput::make('unit_amount')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(3),

                                TextInput::make('total_amount')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(3),
                            ])->columns(12),
                            Placeholder::make('grand_total_placeholder')
                                ->label('Grand Total')
                                ->content(function(Get $get, Set $set){
                                        $total = 0;
                                        if(!$repeaters = $get('items')){
                                            return $total;
                                            
                                        }
                                        foreach($repeaters as $key => $repeater){

                                            $total += $get("items.{$key}.total_amount");
                                        }

                                        $set('grand_total', $total);
                                    
                                        return Number::currency($total, 'PKR');
                                    }),
                                Hidden::make('grand_total')
                                    ->default(0)
                                    ->dehydrated(),
                        
                    ])
                ])->columnSpanFull(),
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->numeric()
                    ->sortable()
                    ->money('PKR'),

                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipping_method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),


                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'new'=>'New',
                        'processing'=> 'Processing',
                        'shipped'=>'Shipped',    
                        'delivered'=> 'Delivered',
                        'cancelled'=> 'Cancelled',
                    ])
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AddressRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
