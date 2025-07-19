<?php

namespace Kaely\PosCustomer\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
} 