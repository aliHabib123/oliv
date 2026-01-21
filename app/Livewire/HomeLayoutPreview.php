<?php

namespace App\Livewire;

use Livewire\Component;

class HomeLayoutPreview extends Component
{
    public array $layoutData = [];

    public function render()
    {
        return view('livewire.home-layout-preview');
    }
}
