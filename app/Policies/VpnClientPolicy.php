<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VpnClient;
use Illuminate\Auth\Access\HandlesAuthorization;

class VpnClientPolicy
{
    use HandlesAuthorization;

//    'before' method runs before any other policy method
    public function before(User $user): ?bool
    {
        if ($user->isAdministrator()) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VpnClient $vpnClient): bool
    {
        return $vpnClient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VpnClient $vpnClient): bool
    {
        return $vpnClient->user_id === $user->id;
    }

    public function updateStatus(User $user, VpnClient $vpnClient): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, VpnClient $vpnClient): bool
    {
        //non-admins cannot delete vpn clients
        return false;
    }

// These can remain false unless you use soft deletes later
    public function restore(User $user, VpnClient $vpnClient): bool
    {
        return false;
    }

    public function forceDelete(User $user, VpnClient $vpnClient): bool
    {
        return false;
    }
}
