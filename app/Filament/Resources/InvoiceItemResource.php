<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceItemResource\Pages;
use App\Filament\Resources\InvoiceItemResource\RelationManagers;
use App\Models\InvoiceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class InvoiceItemResource extends Resource
{
	protected static ?string $model = InvoiceItem::class;

	protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\TextInput::make('name')
					->required()
					->maxLength(255),
				Forms\Components\TextInput::make('quantity')
					->required()
					->numeric(),
				Forms\Components\TextInput::make('price')
					->required()
					->numeric()
					->prefix('$'),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('name')
					->searchable(),
				Tables\Columns\TextColumn::make('quantity')
					->numeric()
					->sortable(),
				Tables\Columns\TextColumn::make('price')
					->money()
					// ->summarize(Sum::make()->money()->label('Total Price')), // Old simple way
					// New Way with custom query
					->summarize(
						Summarizer::make()
							->label('Total')
							->money()
							// This causes an error on PostgreSQL.
							// SQLSTATE[42601]: Syntax error: 7 ERROR: zero-length delimited identifier at or near """"
							// This is the query that it generates
							/*
								SELECT
									sum(price * quantity) AS aggregate
								FROM
									(
										SELECT
										*
										FROM
										"invoice_items"
									) AS ""
						
							*/
							// Flare Link: https://flareapp.io/share/W7zKVMd5
							->using(fn (\Illuminate\Database\Query\Builder $query) => $query->sum(DB::raw('price * quantity')))
							// These also fail
							// ->using(fn (\Illuminate\Database\Query\Builder $query) => $query->sum(DB::raw('price')))
							// ->using(fn (\Illuminate\Database\Query\Builder $query) => $query->sum('price'))

							// This works (and is useless) because it does not internally create a subquery and thus an empty `AS` clause at the end.
							// ->using(fn (\Illuminate\Database\Query\Builder $query) => '5') 
					),
				Tables\Columns\TextColumn::make('created_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('updated_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
			])
			->filters([
				//
			])
			->actions([
				Tables\Actions\EditAction::make(),
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
			//
		];
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ListInvoiceItems::route('/'),
			'create' => Pages\CreateInvoiceItem::route('/create'),
			'edit' => Pages\EditInvoiceItem::route('/{record}/edit'),
		];
	}
}
