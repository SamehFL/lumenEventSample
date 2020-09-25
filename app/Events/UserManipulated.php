<?php


namespace App\Events;

use App\Models\User;
//use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserManipulated extends Event
{
    public $newUser, $originalUser, $action;

    /**
     * Create a new event instance to be triggered with user Creation, Modification, or Deletion.
     * Accepts
     * 1- Actioned User Object
     * 2- Array of Original Object Values to save it in case of Update Action
     * 3- String represent Action Typs
     * Returns void
     */
    public function __construct(User $newUser, array $originalUser, string $action)
    {
        $this->newuser = $newUser;
        $this->originaluser = $originalUser;
        $this->action = $action;
    }
}
