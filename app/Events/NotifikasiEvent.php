<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifikasiEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $userId;

    /**
     * Create a new event instance.
     * @param string $message
     * @param int|null $userId
     */
    public function __construct($message, $userId = null)
    {
        $this->message = $message;
        $this->userId = $userId;
        \Log::info('NotifikasiEvent broadcast', [
            'message' => $message,
            'userId' => $userId,
            'triggered_at' => now()->toDateTimeString(),
            'by' => auth()->check() ? auth()->user()->id : null
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        if ($this->userId) {
            return [new PrivateChannel('notifikasi.' . $this->userId)];
        }
        return [new Channel('notifikasi.global')];
    }

    public function broadcastAs()
    {
        return 'NotifikasiEvent';
    }
}
