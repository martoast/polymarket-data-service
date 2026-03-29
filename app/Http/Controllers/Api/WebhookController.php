<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $webhookSecret = config('cashier.webhook.secret');
        $payload       = $request->getContent();
        $sigHeader     = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default                         => null,
        };

        return response()->json(['received' => true]);
    }

    protected function handleSubscriptionUpdated(object $subscription): void
    {
        $stripeId = $subscription->customer;
        $user     = User::where('stripe_id', $stripeId)->first();

        if (! $user) {
            return;
        }

        $priceId        = $subscription->items->data[0]->price->id ?? null;
        $builderPriceId = config('services.stripe.builder_price_id');
        $proPriceId     = config('services.stripe.pro_price_id');

        $tier = match ($priceId) {
            $proPriceId     => 'pro',
            $builderPriceId => 'builder',
            default         => $user->tier,
        };

        $user->update(['tier' => $tier]);
    }

    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $stripeId = $subscription->customer;
        $user     = User::where('stripe_id', $stripeId)->first();

        if (! $user) {
            return;
        }

        $user->update(['tier' => 'free']);
    }
}
