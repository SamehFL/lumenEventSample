<?php


namespace App\Listeners;

use App\Events\UserManipulated;
use App\Models\UserManipulationsLog;
use Illuminate\Support\Facades\Auth;

class UserManipulationLogging
{
    //The Event Handler that will handle events triggered by create, update, delete events on Users Table
    public function handle(UserManipulated $event){
        //Create a Record for a User Manipulation Log Entry
        $newEntityLog = new UserManipulationsLog();

        //Set Values

            $newEntityLog->entity_id = $event->newuser->id;
            $newEntityLog->action = $event->action;
            Auth::check() ? $newEntityLog->by_user = Auth::user()->id : $newEntityLog->by_user = $event->newuser->id;

            //Set original_values
            if($event->action === 'create'){
                $newEntityLog->original_values = null;
            }elseif ($event->action === 'delete'){
                $newEntityLog->original_values = json_encode($event->newuser);
            }else{
                $newEntityLog->original_values = json_encode($event->originaluser);
            }

            //Set new_values
            if($event->action === 'delete'){
                $newEntityLog->new_values = null;
            }else{
                $newEntityLog->new_values = json_encode($event->newuser);
            }

        //Save Log Record
        $newEntityLog->save();
    }
}
