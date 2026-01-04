<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Select as SelectComponent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (?User $record) => $record === null)
                ->dehydrated(fn ($state) => filled($state)),

            Forms\Components\Select::make('tenant_id')
                ->label('Tenant')
                ->options(fn () => Tenant::query()->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),

            Forms\Components\Select::make('roles')
                ->label('Roles')
                ->multiple()
                ->options(fn () => self::getAssignableRolesForForm())
                ->required()
                ->dehydrated(false)
                ->afterStateHydrated(function (SelectComponent $component, ?User $record): void {
                    if ($record) {
                        $component->state($record->roles->pluck('name')->toArray());
                    }
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('owner')) {
            return $query->where('tenant_id', $user->tenant_id);
        }

        return $query->whereRaw('1 = 0');
    }

    protected static function getAssignableRolesForForm(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            $roles = ['super_admin', 'owner', 'admin', 'editor'];
        } elseif ($user->hasRole('owner')) {
            $roles = ['admin', 'editor'];
        } else {
            $roles = [];
        }

        return collect($roles)
            ->mapWithKeys(function (string $role): array {
                return [$role => Str::headline($role)];
            })
            ->all();
    }

    protected static function canManageUsers(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'owner']);
    }

    public static function canViewAny(): bool
    {
        return self::canManageUsers();
    }

    public static function canCreate(): bool
    {
        return self::canManageUsers();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageUsers();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole('super_admin');
    }
}
