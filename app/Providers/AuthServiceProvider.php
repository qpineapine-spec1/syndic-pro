<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Budget;
use App\Models\Contribution;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Complaint;
use App\Models\Meeting;
use App\Models\Vote;
use App\Models\MeetingRequest;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ServiceProvider as ServiceProviderModel;
use App\Models\Tenant;
use App\Policies\BudgetPolicy;
use App\Policies\ContributionPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\VotePolicy;
use App\Policies\MeetingRequestPolicy;
use App\Policies\MessagePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ServiceProviderPolicy;
use App\Policies\TenantPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Budget::class => BudgetPolicy::class,
        Contribution::class => ContributionPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Expense::class => ExpensePolicy::class,
        Complaint::class => ComplaintPolicy::class,
        Meeting::class => MeetingPolicy::class,
        Vote::class => VotePolicy::class,
        MeetingRequest::class => MeetingRequestPolicy::class,
        Message::class => MessagePolicy::class,
        Notification::class => NotificationPolicy::class,
        ServiceProviderModel::class => ServiceProviderPolicy::class,
        Tenant::class => TenantPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
