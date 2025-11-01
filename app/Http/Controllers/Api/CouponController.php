<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreCouponRequest;
use App\Http\Requests\Api\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends BaseController
{
    /**
     * Display a paginated listing of coupons.
     */
    public function index(Request $request)
    {
        $coupons = Coupon::latest()->paginate(10);
        return $this->sendResponse(CouponResource::collection($coupons), 'Coupons retrieved successfully.');
    }

    /**
     * Store a newly created coupon.
     */
    public function store(StoreCouponRequest $request)
    {
        $coupon = Coupon::create($request->validated());

        return $this->sendResponse(new CouponResource($coupon), 'Coupon created successfully.');
    }

    /**
     * Display the specified coupon.
     */
    public function show(Coupon $coupon)
    {
        return $this->sendResponse(new CouponResource($coupon), 'Coupon retrieved successfully.');
    }

    /**
     * Update the specified coupon.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());

        return $this->sendResponse(new CouponResource($coupon), 'Coupon updated successfully.');
    }

    /**
     * Remove the specified coupon.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return $this->sendSimpleResponse($coupon->id, true, 'Coupon deleted successfully.');
    }
}
