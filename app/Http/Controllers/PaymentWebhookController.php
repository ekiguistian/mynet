<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $provider, PaymentGatewayService $paymentGateway): JsonResponse
    {
        $result = $paymentGateway->processWebhook($provider, $request);

        return response()->json(['success' => true, 'duplicate' => $result['duplicate']]);
    }
}
