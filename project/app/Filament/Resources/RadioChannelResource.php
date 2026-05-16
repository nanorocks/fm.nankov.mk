<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\RadioChannel;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use App\Tables\Columns\CustomImageColumn;
use App\Filament\Resources\RadioChannelResource\Pages;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;

class RadioChannelResource extends Resource
{
    protected static ?string $model = RadioChannel::class;

    protected static \BackedEnum|string|null $navigationIcon = 'gmdi-folder-tt';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\FileUpload::make(RadioChannel::PHOTO)
                    ->directory('photos')
                    ->visibility('public')
                    ->image()
                    ->label('Photo URL'),
                Forms\Components\Radio::make(RadioChannel::PUBLISHED)
                    ->boolean()
                    ->label('Published'),
                Forms\Components\TextInput::make(RadioChannel::TITLE)
                    ->label('Title'),
                Forms\Components\TextInput::make(RadioChannel::AUDIO_URL)
                    ->label('Audio URL'),
                Forms\Components\TextInput::make(RadioChannel::SUBTITLE)
                    ->label('Subtitle'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(RadioChannel::TITLE)
                    ->label('Title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make(RadioChannel::PUBLISHED)
                    ->label('Published')
                    ->sortable()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : 'No'),
                CustomImageColumn::make(RadioChannel::PHOTO)
                    ->label('Photo'),
                TextColumn::make(RadioChannel::SUBTITLE)
                    ->label('Subtitle')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                MediaAction::make('audio')
                    ->media(fn($record) => $record->audio_url)
                    ->label('Play')
                    ->icon('heroicon-o-musical-note')
                    ->autoplay()
                    ->preload(false),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('publish')
                    ->label('Set as Published')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records) {
                        $recordIds = $records->pluck('id')->toArray();
                        RadioChannel::whereIn('id', $recordIds)->update(['published' => true]);
                    })
                    ->requiresConfirmation()
                    ->color('success'),
                BulkAction::make('unpublish')
                    ->label('Set as Unpublished')
                    ->icon('heroicon-o-x-circle')
                    ->action(function ($records) {
                        $recordIds = $records->pluck('id')->toArray();
                        RadioChannel::whereIn('id', $recordIds)->update(['published' => false]);
                    })
                    ->requiresConfirmation()
                    ->color('danger'),
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
            'index' => Pages\ListRadioChannels::route('/'),
            'create' => Pages\CreateRadioChannel::route('/create'),
            'edit' => Pages\EditRadioChannel::route('/{record}/edit'),
        ];
    }
}