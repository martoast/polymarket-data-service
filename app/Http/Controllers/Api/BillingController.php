<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:builder,pro'],
        ]);

        $user = $request->user();
        $plan = $request->input('plan');

        $priceId = $plan === 'pro'
            ? config('services.stripe.pro_price_id', env('STRIPE_PRO_PRICE_ID'))
            : config('services.stripe.builder_price_id', env('STRIPE_BUILDER_PRICE_ID'));

        $frontendUrl = env('APP_FRONTEND_URL', config('app.url'));

        $checkoutSession = $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => $frontendUrl . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $frontendUrl . '/billing/cancel',
            ]);

        return response()->json(['url' => $checkoutSession->url]);
    }

    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();
        $url  = $user->billingPortalUrl(url('/'));

        return response()->json(['url' => $url]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $user         = $request->user();
        $subscription = $user->subscription('default');

        return response()->json([
            'tier'         => $user->tier,
            'status'       => $subscription?->stripe_status,
            'renewal_date' => $subscription?->asStripeSubscription()?->current_period_end
                ? \Carbon\Carbon::createFromTimestamp(
                    $subscription->asStripeSubscription()->current_period_end
                )->toDateTimeString()
                : null,
        ]);
    }
}
