<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Jobs\ChangeAuthenticationMessageJob;
use App\Models\User;
use Exception;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Spatie\Permission\Models\Role;

class UserTable
{
    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatars')
                    ->disk('s3')
                    ->visibility('private')
                    ->defaultImageUrl(fn ($record) => $record->default_avatar)
                    ->circular()
                    ->size(40),

                TextColumn::make('name')
                    ->description(fn(User $record): ?string => $record->userProfile?->company_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'super_admin' => 'primary',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('userProfile.phone')
                    ->label('Phone')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn () => Role::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    })
                    ->native(false),
                SelectFilter::make('receivables')
                    ->options([
                        'YES' => 'Have a debt',
                        'NO' => 'No debt',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'YES') {
                            $query->whereHas('invoices', function (Builder $q) {
                                $q->where('status', '!=', 'paid');
                            });
                        } elseif ($data['value'] === 'NO') {
                            $query->whereDoesntHave('invoices', function (Builder $q) {
                                $q->where('status', '!=', 'paid');
                            });
                        }
                    })
                    ->native(false),
                TrashedFilter::make()
                    ->native(false),
            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()
                        ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('roles', function (Builder $query) {
                            $query->where('name', 'user');
                        }))
                        ->withColumns([
                            Column::make('name')->heading('Name'),
                            Column::make('email')->heading('Email'),
                            Column::make('userProfile.company_name')->heading('Company Name'),
                            Column::make('userProfile.phone')->heading('Phone'),
                            Column::make('receivables')->heading('Receivables')
                                ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),
                        ])
                        ->queue()
                        ->withChunkSize(100)
                        ->askForFilename()
                        ->withFilename(fn ($filename) => time() . '-' . $filename)
                        ->askForWriterType()
                ])
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->before(function ($record, $data) {
                            $emailChanged = $data['email'] !== $record->email;
                            $passwordChanged = !empty($data['password'] ?? '');

                            if ($emailChanged || $passwordChanged) {
                                ChangeAuthenticationMessageJob::dispatch([
                                    'user_name' => $record->name,
                                    'email' => $data['email'],
                                    'password_changed' => $passwordChanged,
                                ], $record->userProfile?->phone);
                            }
                        }),
                    DeleteAction::make()
                        ->disabled(fn(User $record): bool => $record->hasRole('super_admin') || $record->invoices()->exists() || $record->payments()->exists()),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->disabled(fn(User $record): bool => $record->hasRole('super_admin')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
