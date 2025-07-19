<?php

namespace Kaely\PosCustomer\Events;

use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerPointsHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointsEarned
{
    use Dispatchable, SerializesModels;

    public Customer $customer;
    public CustomerPointsHistory $pointsHistory;

    public function __construct(Customer $customer, CustomerPointsHistory $pointsHistory)
    {
        $this->customer = $customer;
        $this->pointsHistory = $pointsHistory;
    }
} 