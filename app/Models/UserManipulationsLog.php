<?php


namespace App\Models;


class UserManipulationsLog extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'entity_id',
        'original_values',
        'new_values',
        'by_user',
        'created_at',
        'updated_at',
    ];

    //Establish relationship between Users Table Actions Log and the User who performed action
    public function User(){
        return $this->hasOne('App\Models\User','id','by_user');
    }
}
