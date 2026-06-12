<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $date = $request->date('date') ?? now();

        $base = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereDate('created_at', $date->toDateString());

        $count = (clone $base)->count();
        $total = (float) (clone $base)->sum('grand_total');

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'transaction_count' => $count,
                'total_sales' => $total,
                'average_order_value' => $count > 0 ? round($total / $count, 2) : 0,
            ],
        ]);
    }
}
