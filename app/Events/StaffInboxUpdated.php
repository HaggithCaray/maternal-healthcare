<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells every open staff page that a patient's unread count changed (a new message, or a
 * colleague read the conversation), so inbox badges update without a reload.
 */
class StaffInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  int  $patientId  the patient whose conversation changed
     * @param  int  $patientUnread  messages from that patient no staff member has read
     * @param  int  $totalUnread  unread messages from all patients (the shared inbox)
     */
    public function __construct(
        public int $patientId,
        public int $patientUnread,
        public int $totalUnread,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('staff-inbox')];
    }
}
