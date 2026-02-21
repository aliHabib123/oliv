<?php

namespace App\Filament\Pages;

use App\Models\Template;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class TemplateBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Template Builder';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $templateId = null;
    public ?string $templateName = null;
    public ?string $industry = null;
    public ?string $style = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin');
    }

    public function getView(): string
    {
        return 'filament.pages.template-builder';
    }

    public function mount(?int $template = null)
    {
        if (! $template) {
            $template = request()->integer('template');
        }

        if (! $template) {
            \Filament\Notifications\Notification::make()
                ->title('Please select a template to edit')
                ->warning()
                ->send();

            return $this->redirect(TemplateResource::getUrl());
        }

        $this->templateId = $template;
        $templateData = Template::find($template);

        if (! $templateData) {
            \Filament\Notifications\Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();

            return $this->redirect(TemplateResource::getUrl());
        }

        $this->templateName = $templateData->name;
        $this->industry = $templateData->industry;
        $this->style = $templateData->style;
    }

    public function saveTemplate(array $data): void
    {
        if (! $this->templateId) {
            \Filament\Notifications\Notification::make()
                ->title('No template selected')
                ->danger()
                ->send();

            return;
        }

        $template = Template::find($this->templateId);

        if (! $template) {
            \Filament\Notifications\Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();

            return;
        }

        $templateData = [
            'layout_json' => $data['gjsData'] ?? null,
            'layout_html' => $data['html'] ?? null,
            'layout_css' => $data['css'] ?? null,
        ];

        $template->update($templateData);

        $this->dispatch('template-saved');
        \Filament\Notifications\Notification::make()
            ->title('Template saved successfully')
            ->success()
            ->send();
    }

    public function getTemplateData(): ?array
    {
        if ($this->templateId) {
            $template = Template::find($this->templateId);
            if ($template) {
                return [
                    'name' => $template->name,
                    'industry' => $template->industry,
                    'style' => $template->style,
                    'gjsData' => $template->layout_json,
                    'html' => $template->layout_html,
                    'css' => $template->layout_css,
                ];
            }
        }
        return null;
    }
}
