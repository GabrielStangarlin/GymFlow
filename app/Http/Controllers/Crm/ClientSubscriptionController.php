<?php

namespace App\Http\Controllers\Crm;

use App\Enums\PaymentMethod;
use App\Exceptions\SubscriptionNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientSubscriptionRequest;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientSubscriptionController extends Controller
{
    public function create(Client $client): View
    {
        return view('crm.clients.subscriptions.create', [
            'client' => $client->load('user'),
            'plans' => Plan::where('active', true)->orderBy('name')->get(),
            'billingPeriods' => BillingPeriod::orderBy('months')->get(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StoreClientSubscriptionRequest $request, Client $client, SubscriptionService $subscriptions): RedirectResponse
    {
        $data = $request->validated();

        try {
            $subscriptions->create(
                $client,
                Plan::findOrFail($data['plan_id']),
                BillingPeriod::findOrFail($data['billing_period_id']),
                PaymentMethod::from($data['payment_method']),
                $request->boolean('paid_now'),
                $request->user(),
            );
        } catch (SubscriptionNotAllowedException $e) {
            return back()->withInput()->withErrors(['subscription' => $e->getMessage()]);
        }

        return redirect()->route('crm.clients.show', $client)->with('status', 'Assinatura criada.');
    }
}
