<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientId;
    public $readByUserId;

    /**
     * @param  int  $patientId  the patient whose conversation was read
     * @param  int  $readByUserId  the patient (read the staff's replies) or a staff member (read the patient's messages)
     */
    public function __construct($patientId, $readByUserId)
    {
        $this->patientId = $patientId;
        $this->readByUserId = $readByUserId;
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
