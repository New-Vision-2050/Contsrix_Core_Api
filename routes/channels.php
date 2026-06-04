<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Employee Task inbox notifications - one channel per action taker
Broadcast::channel('inbox.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
Broadcast::channel('employee-task.inbox-counts.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
Broadcast::channel('employee-task.notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

//// Allow users to listen to their company's channel
//Broadcast::channel('company.{companyId}', function ($user, $companyId) {
//    return (string) $user->company_id === (string) $companyId;
//});
