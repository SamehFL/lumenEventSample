<?php


namespace App\Transformers;

use App\Models\User;
use App\Models\UserManipulationsLog;
use League\Fractal\TransformerAbstract;

class UserManipulationsLogTransformer extends TransformerAbstract
{
    //Format log entities in more readable form
    public function transform (UserManipulationsLog $userTableLogEntry){
        return[
            'id'=>  $userTableLogEntry->id,
            'action'=>$userTableLogEntry->action,
            'manipulated_user'=>$userTableLogEntry->entity_id,
            'original_values'=>json_decode($userTableLogEntry->original_values,true),
            'new_values'=>json_decode($userTableLogEntry->new_values,true),
            //If action taking user is physically deleted it will be shown as deleted user
            'action_taken_by_user' =>[
                'id'=>$userTableLogEntry->by_user,
                'name'=>is_null($userTableLogEntry->User)  ? '[Deleted]': $userTableLogEntry->User->name,
            ],
            //format Date in a more readable format
            'action_taken_at'=>$userTableLogEntry->created_at->format('jS F Y'),
        ];
    }
}
