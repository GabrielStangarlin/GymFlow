<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('crm.plans.index', ['plans' => Plan::with('timeWindows')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('crm.plans.create');
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $plan = Plan::create($this->planData($request));
            $plan->timeWindows()->createMany($request->validated('windows'));
        });

        return redirect()->route('crm.plans.index')->with('status', 'Plano criado.');
    }

    public function edit(Plan $plan): View
    {
        return view('crm.plans.edit', ['plan' => $plan->load('timeWindows')]);
    }

    public function update(StorePlanRequest $request, Plan $plan): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan): void {
            $plan->update($this->planData($request));
            $plan->timeWindows()->delete();
            $plan->timeWindows()->createMany($request->validated('windows'));
        });

        return redirect()->route('crm.plans.index')->with('status', 'Plano atualizado.');
    }

    /**
     * @return array{name: string, monthly_price: string, active: bool}
     */
    private function planData(StorePlanRequest $request): array
    {
        return [
            'name' => $request->validated('name'),
            'monthly_price' => $request->validated('monthly_price'),
            'active' => $request->boolean('active'),
        ];
    }
}
