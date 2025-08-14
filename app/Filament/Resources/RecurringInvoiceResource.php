<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringInvoiceResource\Pages;
use App\Filament\Resources\RecurringInvoiceResource\Schemas\RecurringInvoiceForm;
use App\Filament\Resources\RecurringInvoiceResource\Schemas\RecurringInvoiceTable;
use App\Models\RecurringInvoice;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class RecurringInvoiceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = RecurringInvoice::class;
    protected static ?string $slug = 'recurring-invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

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
        ];
    }

    public static function form(Form $form): Form
    {
        return RecurringInvoiceForm::form($form);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return RecurringInvoiceTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringInvoices::route('/'),
            'create' => Pages\CreateRecurringInvoice::route('/create'),
            'edit' => Pages\EditRecurringInvoice::route('/{record}/edit'),
            'view' => Pages\ViewRecurringInvoice::route('/{record}'),
            'manage-invoices' => Pages\ManageInvoices::route('/{record}/manage-invoices'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code'];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditRecurringInvoice::class,
            Pages\ViewRecurringInvoice::class,
            Pages\ManageInvoices::class
        ]);
    }
}
