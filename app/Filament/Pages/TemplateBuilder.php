<?php

namespace App\Filament\Pages;

use App\Models\Template;
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

    public function mount(?int $template = null): void
    {
        if ($template) {
            $this->templateId = $template;
            $templateData = Template::find($template);
            if ($templateData) {
                $this->templateName = $templateData->name;
                $this->industry = $templateData->industry;
                $this->style = $templateData->style;
            }
        }
    }

    public function saveTemplate(array $data): void
    {
        $templateData = [
            'name' => $data['name'] ?? 'Untitled Template',
            'industry' => $data['industry'] ?? null,
            'style' => $data['style'] ?? null,
            'layout_json' => $data['gjsData'] ?? null,
            'layout_html' => $data['html'] ?? null,
            'layout_css' => $data['css'] ?? null,
            'created_by' => Auth::id(),
        ];

        if ($this->templateId) {
            Template::where('id', $this->templateId)->update($templateData);
        } else {
            $template = Template::create($templateData);
            $this->templateId = $template->id;
        }

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
