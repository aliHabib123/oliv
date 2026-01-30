<?php

namespace App\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;

class NotificationPreview extends Component
{
    #[Reactive]
    public array $notificationData = [];

    protected $listeners = ['updatePreview'];

    public function updatePreview($data): void
    {
        $this->notificationData = $data;
        $this->dispatch('preview-updating');
    }

    public function render()
    {
        return view('filament.components.notification-preview', [
            'notificationData' => $this->notificationData,
        ]);
    }
}
