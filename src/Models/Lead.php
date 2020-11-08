<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use LaravelEncryptableTrait;
    
    protected $table = 'crm_leads';
    
    protected $guarded = [];

    protected $encryptable = [
        'first_name',
        'middle_name',
        'last_name',
        'salutation',
        'maiden_name',
        'licence',
        'photo_url',
        'linkedin_profile_pic',
        'facebook_profile_pic',
        'twitter_profile_pic',
    ];
}
