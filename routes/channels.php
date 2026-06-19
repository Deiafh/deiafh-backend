<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Per-branch live orders feed. A user may listen to a branch only if it is
// among their allowed branches — or if they have no restriction (full access).
Broadcast::channel('dashboard.orders.branch.{branchId}', function ($user, $branchId) {
    if ($user === null) {
        return false;
    }

    $allowed = $user->allowedBranchIds();

    return empty($allowed) || in_array((int) $branchId, $allowed, true);
});
