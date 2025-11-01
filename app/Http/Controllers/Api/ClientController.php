<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreClientRequest;
use App\Http\Requests\Api\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ClientController extends BaseController
{
    public function index(): JsonResponse
    {
        $clients = Client::latest()->paginate(10);
        return $this->sendResponse(ClientResource::collection($clients), 'Clients fetched successfully.');
    }
    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $clients = Client::select('id', 'title', 'logo')->active()->latest()->get();
        return $this->sendResponse(
            ClientResource::collection($clients),
            'Clients retrieved successfully.'
        );
    }
    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('clients', 'public');
        }

        $client = Client::create($data);

        return $this->sendSimpleResponse($client->id, true, 'Client created successfully.');
    }

    public function show(Client $client): JsonResponse
    {
        return $this->sendResponse(new ClientResource($client), 'Client details retrieved successfully.');
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($client->logo && Storage::disk('public')->exists($client->logo)) {
                Storage::disk('public')->delete($client->logo);
            }
            $data['logo'] = $request->file('logo')->store('clients', 'public');
        }

        $client->update($data);

        return $this->sendSimpleResponse($client->id, true, 'Client updated successfully.');
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->logo && Storage::disk('public')->exists($client->logo)) {
            Storage::disk('public')->delete($client->logo);
        }

        $client->delete();

        return $this->sendSimpleResponse($client->id, true, 'Client deleted successfully.');
    }
}
