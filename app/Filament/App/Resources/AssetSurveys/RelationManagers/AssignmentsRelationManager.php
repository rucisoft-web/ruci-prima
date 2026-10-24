<?php

namespace App\Filament\App\Resources\AssetSurveys\RelationManagers;

use App\Enums\AssignmentState;
use App\Filament\App\Pages\VerifikasiAset;
use App\Models\AssetSurveyAssignment;
use App\Models\AssetTemplate;
use App\Models\Location;
use App\Models\Membership;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Penugasan tim';

    protected static ?string $modelLabel = 'Penugasan';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canOperate() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('location_id')->label('Lokasi')->required()->searchable()
                ->options(fn () => Location::query()->where('is_active', true)->orderBy('complete_name')->pluck('complete_name', 'id')),
            TextInput::make('team_name')->label('Nama tim')->required(),
            Select::make('surveyors')->label('Surveyor')->multiple()->relationship('surveyors', 'name')
                ->options(fn () => Membership::query()->with('user')->where('is_active', true)->get()->pluck('user.name', 'user_id')),
            Select::make('asset_template_id')->label('Templat acuan')->options(fn () => AssetTemplate::query()->orderBy('name')->pluck('name', 'id')),
            Select::make('state')->label('Status')->options(AssignmentState::class)->default(AssignmentState::Draft)->required(),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $count = fn (string $key) => fn (AssetSurveyAssignment $record) => $record->counts()[$key];

        return $table
            ->columns([
                TextColumn::make('location.complete_name')->label('Lokasi')->wrap(),
                TextColumn::make('team_name')->label('Tim'),
                TextColumn::make('surveyors.name')->label('Surveyor')->badge(),
                TextColumn::make('template.name')->label('Templat')->placeholder('—')->toggleable(),
                TextColumn::make('expected')->label('Diharapkan')->state($count('expected')),
                TextColumn::make('verified')->label('Terverifikasi')->state($count('verified'))->color('success'),
                TextColumn::make('missing')->label('Tidak ada')->state($count('missing'))->color('danger'),
                TextColumn::make('additional')->label('Tambahan')->state($count('additional'))->color('warning'),
                TextColumn::make('progress')->label('Kemajuan')->state(fn (AssetSurveyAssignment $record) => $record->counts()['progress'].'%'),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('verify')->label('Verifikasi')->icon(Heroicon::OutlinedCheckBadge)
                    ->url(fn (AssetSurveyAssignment $record) => VerifikasiAset::getUrl([
                        'filters' => ['asset_survey_id' => ['value' => $record->asset_survey_id], 'location_id' => ['value' => $record->location_id]],
                    ])),
                Action::make('start')->label('Mulai')->icon(Heroicon::OutlinedPlay)
                    ->visible(fn (AssetSurveyAssignment $record) => ! $this->isReadOnly() && in_array($record->state, [AssignmentState::Draft, AssignmentState::Assigned], true))
                    ->action(fn (AssetSurveyAssignment $record) => $record->update(['state' => AssignmentState::InProgress])),
                Action::make('done')->label('Selesai')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (AssetSurveyAssignment $record) => ! $this->isReadOnly() && $record->state === AssignmentState::InProgress)
                    ->action(fn (AssetSurveyAssignment $record) => $record->update(['state' => AssignmentState::Done])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
