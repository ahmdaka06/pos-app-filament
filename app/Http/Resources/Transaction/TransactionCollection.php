<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Shared\BaseCollection;

class TransactionCollection extends BaseCollection
{
    public $collects = TransactionResource::class;
}
