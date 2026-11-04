<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\BillingPeriod;
use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientEnrollmentService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * @param  array{name: string, email: string, phone: string, nif: string, birth_date: string}  $clientData
     */
    public function enroll(
        array $clientData,
        Plan $plan,
        BillingPeriod $billingPeriod,
        PaymentMethod $paymentMethod,
        bool $paidNow,
        User $recordedBy,
    ): Client {
        $client = DB::transaction(function () use ($clientData, $plan, $billingPeriod, $paymentMethod, $paidNow, $recordedBy): Client {
            $user = User::create([
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => Str::password(32),
                'role' => Role::Client,
                'active' => true,
            ]);

            $client = $user->client()->create([
                'phone' => $clientData['phone'],
                'nif' => $clientData['nif'],
                'birth_date' => $clientData['birth_date'],
                'qr_token' => Str::random(64),
                'active' => true,
            ]);

            $this->subscriptions->create($client, $plan, $billingPeriod, $paymentMethod, $paidNow, $recordedBy);

            return $client;
        });

        $client->user->sendActivationNotification();

        return $client;
    }
}
