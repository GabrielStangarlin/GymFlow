<?php

namespace App\Http\Controllers\Crm;

use App\Exceptions\SubscriptionNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q'));

        $clients = Client::with(['user', 'subscriptions' => fn ($query) => $query->whereNull('ended_on')->with('plan')->latest('id')])
            ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term): void {
                $query->where('nif', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            }))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('crm.clients.index', ['clients' => $clients, 'term' => $term]);
    }

    public function show(Client $client): View
    {
        $client->load([
            'user',
            'subscriptions' => fn ($query) => $query->with(['plan', 'billingPeriod', 'payments.recorder'])->latest('id'),
            'accessLogs' => fn ($query) => $query->latest('scanned_at')->limit(20),
        ]);

        return view('crm.clients.show', [
            'client' => $client,
            'current' => $client->currentSubscription(),
            'payments' => $client->subscriptions->flatMap->payments->sortByDesc('paid_at'),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('crm.clients.edit', ['client' => $client->load('user')]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client): void {
            $client->user->update($request->safe()->only(['name', 'email']));
            $client->update($request->safe()->only(['phone', 'nif', 'birth_date']));
        });

        return redirect()->route('crm.clients.show', $client)->with('status', 'Dados do aluno atualizados.');
    }

    public function deactivate(Client $client): RedirectResponse
    {
        try {
            DB::transaction(fn () => $client->deactivate());
        } catch (SubscriptionNotAllowedException $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }

        return back()->with('status', 'Aluno desativado.');
    }

    public function resendActivation(Client $client): RedirectResponse
    {
        $client->user->sendActivationNotification();

        return back()->with('status', 'Email de ativação reenviado.');
    }
}
