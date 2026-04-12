<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//// Allow users to listen to their company's channel
//Broadcast::channel('company.{companyId}', function ($user, $companyId) {
//    return (string) $user->company_id === (string) $companyId;
//});
