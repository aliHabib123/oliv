<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\On;
use Livewire\Component;

class HomeLayoutPreview extends Component
{
    public array $layoutData = [];

    public ?Tenant $tenant = null;

    #[On('preview-updated')]
    public function updatePreview($data)
    {
        $this->layoutData = $data;
    }

    public function render()
    {
        return view('filament.components.home-layout-preview');
    }
}
