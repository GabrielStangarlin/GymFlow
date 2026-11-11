<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBillingPeriodRequest;
use App\Models\BillingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingPeriodController extends Controller
{
    public function edit(): View
    {
        return view('crm.billing-periods.edit', ['periods' => BillingPeriod::orderBy('months')->get()]);
    }

    public function update(UpdateBillingPeriodRequest $request): RedirectResponse
    {
        $discounts = $request->validated('discounts');

        DB::transaction(function () use ($discounts): void {
            foreach (BillingPeriod::whereKey(array_keys($discounts))->get() as $period) {
                $period->update(['discount_percent' => $discounts[$period->id]]);
            }
        });

        return redirect()->route('crm.billing-periods.edit')->with('status', 'Descontos atualizados.');
    }
}
