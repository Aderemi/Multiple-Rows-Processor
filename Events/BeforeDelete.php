<?php

namespace MultipleRows\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;

class BeforeDelete
{
    use InteractsWithSockets, SerializesModels;

    protected $datum;
    protected $model;

    /**
     * Create a new event instance.
     *
     * @param array $datum
     * @param Model $model
     */
    public function __construct(array $datum, Model $model)
    {
        $this->datum = $datum;
        $this->model = $model;
    }

    public function getData() : array
    {
        return $this->datum;
    }

    public function getModel() :  Model
    {
        return $this->model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
