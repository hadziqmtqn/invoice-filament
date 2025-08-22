<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\Schemas\PaymentForm;
use App\Filament\Resources\PaymentResource\Schemas\PaymentTable;
use App\Filament\Resources\PaymentResource\Widgets\TotalPaymentOverview;
use App\Models\Payment;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PaymentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Payment::class;
    protected static ?string $slug = 'payments';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return PaymentForm::form($form);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return PaymentTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'invoicePayments.invoice',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewPayment::class,
            Pages\EditPayment::class
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'user.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->reference_number;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'User' => $record->user?->name,
            'Date' => Carbon::parse($record->date)->isoFormat('D MMM Y'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user']);
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return PaymentResource::getUrl('view', ['record' => $record]);
    }

    public static function getWidgets(): array
    {
        return [
            TotalPaymentOverview::class
        ];
    }
}
