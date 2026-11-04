<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gymflow:end-overdue')]
#[Description('Termina as assinaturas com mais de 2 dias em atraso')]
class EndOverdueSubscriptions extends Command
{
    public function handle(SubscriptionService $subscriptions): int
    {
        $ended = $subscriptions->endOverdue(today());

        $this->info("Assinaturas terminadas: {$ended}");

        return self::SUCCESS;
    }
}
