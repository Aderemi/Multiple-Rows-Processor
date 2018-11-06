<?php
/**
 * Created by PhpStorm.
 * User: Dammyololade
 * Date: 8/27/2018
 * Time: 10:45 AM
 */

namespace MultipleRows\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AfterCreate
{
    use InteractsWithSockets, SerializesModels;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $data;

    /**
     * AfterCreate constructor.
     * @param array $data: the currently processed data
     * @param Model $model
     */
    public function __construct(array $data, Model $model)
    {
        $this->model = $model;
        $this->data = $data;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getData()
    {
        return $this->data;
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