<?php

namespace App\Http\Controllers;

use App\Services\PaymentCallbackService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentCallbackService $paymentCallbackService;

    /**
     * @param PaymentCallbackService $paymentCallbackService
     */
    public function __construct(PaymentCallbackService $paymentCallbackService)
    {
        $this->paymentCallbackService = $paymentCallbackService;
    }

    public function callback(Request $request, $provider)
    {
        return $this->paymentCallbackService->handle($request, $provider);
    }
}
