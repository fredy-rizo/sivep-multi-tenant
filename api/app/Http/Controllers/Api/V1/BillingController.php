<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        protected TenantContext $tenant,
        protected PaymentGateway $gateway,
    ) {}

    public function plans(): JsonResponse
    {
        $plans = Plan::query()
            ->with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => $this->formatPlan($plan));

        return response()->json(['data' => $plans]);
    }

    public function current(): JsonResponse
    {
        $subscription = $this->activeSubscription();

        return response()->json([
            'data' => $subscription ? $this->formatSubscription($subscription) : null,
            'gateway' => $this->gateway->name(),
        ]);
    }

    public function changePlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'billing_cycle' => ['sometimes', Rule::in(['monthly', 'yearly'])],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $cycle = $data['billing_cycle'] ?? 'monthly';
        $start = now()->startOfDay();
        $end = $cycle === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth();

        $charge = $this->gateway->charge([
            'organization_id' => $this->tenant->id(),
            'plan_id' => $plan->id,
            'amount' => $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
            'cycle' => $cycle,
        ]);

        // El gateway manual deja el cobro pendiente, pero la suscripción se
        // activa para no bloquear al cliente mientras se confirma el pago.
        $status = $charge['status'] === 'pending' ? 'active' : $charge['status'];

        $subscription = Subscription::updateOrCreate(
            ['organization_id' => $this->tenant->id()],
            [
                'plan_id' => $plan->id,
                'status' => $status,
                'billing_cycle' => $cycle,
                'amount' => $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
                'current_period_start' => $start->toDateString(),
                'current_period_end' => $end->toDateString(),
                'cancel_at_period_end' => false,
            ],
        );

        $subscription->load('plan.features');

        return response()->json([
            'data' => $this->formatSubscription($subscription),
            'payment' => $charge,
        ]);
    }

    public function cancel(): JsonResponse
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return response()->json(['message' => 'No hay una suscripción activa.'], 404);
        }

        $subscription->update(['cancel_at_period_end' => true]);

        return response()->json([
            'message' => 'La suscripción se cancelará al final del periodo.',
            'data' => $this->formatSubscription($subscription->load('plan.features')),
        ]);
    }

    protected function activeSubscription(): ?Subscription
    {
        return Subscription::query()
            ->with('plan.features')
            ->where('organization_id', $this->tenant->id())
            ->whereIn('status', ['active', 'trialing'])
            ->whereDate('current_period_end', '>=', now()->toDateString())
            ->latest('current_period_end')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPlan(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_monthly' => $plan->price_monthly,
            'price_yearly' => $plan->price_yearly,
            'trial_days' => $plan->trial_days,
            'features' => $plan->features->map(fn ($feature) => [
                'code' => $feature->code,
                'name' => $feature->name,
                'category' => $feature->category,
                'kind' => $feature->kind,
                'limit_value' => $feature->pivot->limit_value,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatSubscription(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'billing_cycle' => $subscription->billing_cycle,
            'amount' => $subscription->amount,
            'current_period_start' => $subscription->current_period_start?->toDateString(),
            'current_period_end' => $subscription->current_period_end?->toDateString(),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'days_left' => $subscription->current_period_end
                ? max(0, (int) Carbon::now()->startOfDay()->diffInDays($subscription->current_period_end, false))
                : null,
            'plan' => $this->formatPlan($subscription->plan),
        ];
    }
}
