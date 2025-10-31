<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreDeliveryPincodeRequest;
use App\Http\Requests\Api\UpdateDeliveryPincodeRequest;
use App\Http\Resources\DeliveryPincodeResource;
use App\Models\DeliveryPincode;
use Illuminate\Http\JsonResponse;

class DeliveryPincodeController extends BaseController
{
    public function index(): JsonResponse
    {
        $pincodes = DeliveryPincode::select('id', 'pincode', 'shipping_cost', 'is_serviceable', 'delivery_days_min', 'delivery_days_max')->latest()->get();
        return $this->sendResponse(DeliveryPincodeResource::collection($pincodes), 'Delivery pincodes fetched successfully.');
    }
    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $pincodes = DeliveryPincode::select('id', 'pincode', 'shipping_cost', 'is_serviceable', 'delivery_days_min', 'delivery_days_max')->serviceable()->latest()->get();
        return $this->sendResponse(
            DeliveryPincodeResource::collection($pincodes),
            'Delivery pincodes retrieved successfully.'
        );
    }
    public function store(StoreDeliveryPincodeRequest $request): JsonResponse
    {
        $pincode = DeliveryPincode::create($request->validated());
        return $this->sendSimpleResponse($pincode->id, true, 'Delivery pincode created successfully.');
    }

    public function show(DeliveryPincode $deliveryPincode): JsonResponse
    {
        return $this->sendResponse(new DeliveryPincodeResource($deliveryPincode), 'Delivery pincode details retrieved successfully.');
    }

    public function update(UpdateDeliveryPincodeRequest $request, DeliveryPincode $deliveryPincode): JsonResponse
    {
        $deliveryPincode->update($request->validated());
        return $this->sendSimpleResponse($deliveryPincode->id, true, 'Delivery pincode updated successfully.');
    }

    public function destroy(DeliveryPincode $deliveryPincode): JsonResponse
    {
        $deliveryPincode->delete();
        return $this->sendSimpleResponse($deliveryPincode->id, true, 'Delivery pincode deleted successfully.');
    }
}
