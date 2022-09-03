<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        // Pagination
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil!'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ditemukan',
                    404
                );
            }
        }

        $transaction = Transaction::with(['items.product'])->where('user_id', Auth::user()->id);
        // Filter status
        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaksi berhasi diambil.'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            // Maksudnya * adalah apabila ada 3 item maka akan dicek semua apakah ada di dalam DB atau tidak
            'items.*' => 'exists:product,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELED,FAILED,SHIPPING,SHIPPED'
        ]);

        $transaction = Transaction::create([
            'user_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        foreach ($request->items as $product) {
            TransactionDetail::create([
                'user_id' => Auth::user()->id,
                'product_id' => $product['id'],
                'transaction_id' => $transaction->id,
                'quantity' => $product['quantity'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil!');
    }
}
