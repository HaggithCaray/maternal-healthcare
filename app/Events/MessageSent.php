<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $patientId;

    /**
     * @param  \App\Models\ChatMessage  $message  sent with its sender (id, name, role) loaded
     * @param  int  $patientId  the patient whose conversation this belongs to
     */
    public function __construct($message, $patientId)
    {
        $this->message = $message;
        $this->patientId = $patientId;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient-chat.' . $this->patientId),
        ];
    }
}
