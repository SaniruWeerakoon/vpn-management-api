<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProvisionVpnClientJob;
use App\Jobs\RevokeVpnClientJob;
use App\Models\VpnClient;
use Cache;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VpnClientController extends Controller
{
    private function scoped()
    {
        $user = auth()->user();

        return ($user && $user->isAdministrator())
            ? VpnClient::query()
            : VpnClient::where('user_id', $user->id);
    }

    public function index()
    {
        Gate::authorize('viewAny', VpnClient::class);

        $cacheKey = auth()->user()->isAdministrator() ? 'vpn_clients:list:admin:v1' : 'vpn_clients:list:user:' . auth()->id() . ':v1';

        return Cache::remember($cacheKey, 60, function () {
            return $this->scoped()
                ->select(
                    'id',
                    'client_name',
                    'status',
                    'created_at',
                )
                ->get();
        });
    }

    public function add(Request $request)
    {
        Gate::authorize('create', VpnClient::class);
        $data = $request->validate([
            'client_name' => ['required', 'string', 'between:3,50', 'unique:vpn_clients,client_name'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Non-admins cannot set status
        if (!auth()->user()->isAdministrator()) {
            unset($data['status']);
        }

        $data['user_id'] = auth()->id();

        $client = VpnClient::create($data);
        return $client->refresh();
    }

    public function show($id)
    {
        $vpnClient = $this->scoped()->whereKey($id)->firstOrFail();
        Gate::authorize('view', $vpnClient);
        return $vpnClient;
    }

    public function update(Request $request, $id)
    {
        $vpnClient = $this->scoped()->whereKey($id)->firstOrFail();
        Gate::authorize('update', $vpnClient);

        $data = $request->validate([
            'client_name' => ['required', 'unique:vpn_clients,client_name,' . $vpnClient->id],
            'status' => ['nullable'],
            'notes' => ['nullable'],
        ]);

        $vpnClient->update($data);

        return $vpnClient;
    }

    public function destroy($id)
    {
        $vpnClient = $this->scoped()->whereKey($id)->firstOrFail();
        Gate::authorize('delete', $vpnClient);
        $vpnClient->delete();

        return response()->noContent();
    }

    public function provision($id)
    {
        $client = VpnClient::findOrFail($id);
        ProvisionVpnClientJob::dispatch($client->id);
        return response()->json(['status' => 'queued']);
    }

    public function revoke($id)
    {
        $client = VpnClient::findOrFail($id);
        RevokeVpnClientJob::dispatch($client->id);
        return response()->json(['status' => 'queued']);
    }
}
