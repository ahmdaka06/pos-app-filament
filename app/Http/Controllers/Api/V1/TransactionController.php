<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateSaleAction;
use App\Actions\RefundTransactionAction;
use App\Actions\VoidTransactionAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransactionRequest;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Http\Resources\Transaction\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()->latest();

        if ($request->boolean('mine')) {
            $query->mine($request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return new TransactionCollection($query->paginate(20));
    }

    public function store(StoreTransactionRequest $request, CreateSaleAction $action)
    {
        $key = $request->header('Idempotency-Key');
        $existing = $key ? Transaction::where('idempotency_key', $key)->first() : null;

        try {
            $transaction = $action->execute($request->validated(), $request->user(), $key);
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['items' => [$e->getMessage()]]);
        }

        $status = ($existing && $existing->id === $transaction->id) ? 200 : 201;

        return (new TransactionResource($transaction->load('items')))->response()->setStatusCode($status);
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load('items'));
    }

    public function void(Transaction $transaction, VoidTransactionAction $action)
    {
        $this->authorize('void', $transaction);

        return new TransactionResource($action->execute($transaction, request()->user())->load('items'));
    }

    public function refund(Transaction $transaction, RefundTransactionAction $action)
    {
        $this->authorize('refund', $transaction);

        return new TransactionResource($action->execute($transaction, request()->user())->load('items'));
    }
}
