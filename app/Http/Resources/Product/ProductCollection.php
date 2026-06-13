<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Shared\BaseCollection;

class ProductCollection extends BaseCollection
{
    public $collects = ProductResource::class;
}
