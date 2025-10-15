<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class MessagesReadAll implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Chat $chat, public User $user)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chat->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'read_at' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read-all';
    }
}


