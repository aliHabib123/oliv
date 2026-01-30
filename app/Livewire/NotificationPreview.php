<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationPreview extends Component
{
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
