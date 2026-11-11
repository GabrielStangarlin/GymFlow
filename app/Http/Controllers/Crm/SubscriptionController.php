<?php

namespace App\Http\Controllers\Crm;

use App\Exceptions\SubscriptionNotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status');

        $subscriptions = Subscription::with(['client.user', 'plan', 'billingPeriod'])
            ->withStatus($status)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('crm.subscriptions.index', ['subscriptions' => $subscriptions, 'status' => $status]);
    }

    public function pay(Request $request, Subscription $subscription): RedirectResponse
    {
        return $this->perform(fn () => $this->subscriptions->recordPayment($subscription, $request->user()), 'Pagamento registado.');
    }

    public function settleDebt(Request $request, Subscription $subscription): RedirectResponse
    {
        return $this->perform(fn () => $this->subscriptions->settleDebt($subscription, $request->user()), 'Dívida regularizada.');
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        return $this->perform(fn () => $this->subscriptions->cancel($subscription), 'Assinatura cancelada.');
    }

    private function perform(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (SubscriptionNotAllowedException $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
