<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Shared\BaseCollection;

class CustomerCollection extends BaseCollection
{
    public $collects = CustomerResource::class;
}
