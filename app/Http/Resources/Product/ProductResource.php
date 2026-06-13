<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'price' => $this->price,
            'unit' => $this->unit,
            'stock' => $this->stock,
            'category_id' => $this->category_id,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
        ];
    }
}
