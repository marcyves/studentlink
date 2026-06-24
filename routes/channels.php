<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('group.{groupId}', function ($user, int $groupId) {
    return Group::query()
        ->whereKey($groupId)
        ->whereHas('members', fn ($q) => $q->where('users.id', $user->id))
        ->exists();
});
