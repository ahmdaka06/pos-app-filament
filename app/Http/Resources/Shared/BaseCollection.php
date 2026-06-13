<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'page' => $this->resource->currentPage(),
                'limit' => $this->resource->perPage(),
                'total_pages' => $this->resource->lastPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
