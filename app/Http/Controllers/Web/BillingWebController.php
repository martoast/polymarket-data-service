<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingWebController extends Controller
{
    public function index(Request $request): View
    {
        $user         = $request->user();
        $subscription = $user->subscription('default');

        return view('billing', compact('user', 'subscription'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:builder,pro'],
        ]);

        $user  = $request->user();
        $plan  = $request->input('plan');

        $priceId = $plan === 'pro'
            ? config('services.stripe.pro_price_id')
            : config('services.stripe.builder_price_id');

        $checkoutSession = $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing') . '?checkout=success',
                'cancel_url'  => route('billing') . '?checkout=cancelled',
            ]);

        return redirect($checkoutSession->url);
    }

    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();
        $url  = $user->billingPortalUrl(route('billing'));

        return redirect($url);
    }
}
