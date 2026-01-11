<?php

namespace App\Filament\Widgets;

use App\Models\CheckoutEvent;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentCheckoutsTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $tenantId = null;

        if ($user && method_exists($user, 'tenant')) {
            $tenant = $user->tenant;
            $tenantId = $tenant?->id;
        }

        return $table
            ->heading('Recent Checkouts')
            ->query(
                CheckoutEvent::query()
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('checkout_id')
                    ->label('Checkout ID')
                    ->limit(20)
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_email')
                    ->label('Customer')
                    ->default('Guest')
                    ->searchable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'checkout_created' => 'warning',
                        'order_completed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('Pending')
                    ->sortable(),
            ]);
    }
}
