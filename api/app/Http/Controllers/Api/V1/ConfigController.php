<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'roles'            => User::ROLES,
            'order_statuses'   => Order::STATUSES,
            'order_sources'    => Order::SOURCES,
            'payment_methods'  => Order::PAYMENT_METHODS,
            'payment_statuses' => Payment::STATUSES,
            'coupon_types'     => Coupon::TYPES,
        ]);
    }
}