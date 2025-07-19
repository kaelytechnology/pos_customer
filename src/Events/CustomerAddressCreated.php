<?php

namespace Kaely\PosCustomer\Events;

use Kaely\PosCustomer\Models\CustomerAddress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerAddressCreated
{
    use Dispatchable, SerializesModels;

    public CustomerAddress $address;

    public function __construct(CustomerAddress $address)
    {
        $this->address = $address;
    }
} 