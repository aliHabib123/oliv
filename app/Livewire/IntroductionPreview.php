<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\On;
use Livewire\Component;

class IntroductionPreview extends Component
{
    public array $introData = [];

    public ?Tenant $tenant = null;

    #[On('preview-updated')]
    public function updatePreview($data)
    {
        $this->introData = $data;
    }

    public function render()
    {
        return view('filament.components.introduction-preview');
    }
}
