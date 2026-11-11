<?php

namespace App\Http\Controllers\Crm;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\BillingPeriod;
use App\Models\Plan;
use App\Services\ClientEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function create(): View
    {
        return view('crm.enrollments.create', [
            'plans' => Plan::where('active', true)->orderBy('name')->get(),
            'billingPeriods' => BillingPeriod::orderBy('months')->get(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StoreEnrollmentRequest $request, ClientEnrollmentService $enrollment): RedirectResponse
    {
        $data = $request->validated();

        $client = $enrollment->enroll(
            $request->safe()->only(['name', 'email', 'phone', 'nif', 'birth_date']),
            Plan::findOrFail($data['plan_id']),
            BillingPeriod::findOrFail($data['billing_period_id']),
            PaymentMethod::from($data['payment_method']),
            $request->boolean('paid_now'),
            $request->user(),
        );

        $message = 'Aluno inscrito. Foi enviado o email de ativação.';

        if (Route::has('crm.clients.show')) {
            return redirect()->route('crm.clients.show', $client)->with('status', $message);
        }

        return redirect()->route('crm.enrollments.create')->with('status', $message);
    }
}
