<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PickupRfidController extends Controller
{
    public function show()
    {
        return view('pickup-rfid');
    }

    public function confirm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $orderId = strtoupper(trim((string) $request->input('order_id')));

            DB::connection('oracle')->transaction(function () use ($orderId) {
                $orderExists = DB::connection('oracle')
                    ->table('orders')
                    ->whereRaw('UPPER(order_id) = ?', [$orderId])
                    ->exists();

                if (!$orderExists) {
                    throw new \RuntimeException('Invalid Order ID: ' . $orderId);
                }

                $itemsToCollect = DB::connection('oracle')
                    ->table('order_item')
                    ->whereRaw('UPPER(order_id) = ?', [$orderId])
                    ->whereRaw("NVL(UPPER(item_status), 'PENDING') NOT IN ('COLLECTED', 'CANCELLED')")
                    ->count();

                if ($itemsToCollect < 1) {
                    throw new \RuntimeException('No pending or ready items found for this order.');
                }

                DB::connection('oracle')
                    ->table('order_item')
                    ->whereRaw('UPPER(order_id) = ?', [$orderId])
                    ->whereRaw("NVL(UPPER(item_status), 'PENDING') NOT IN ('COLLECTED', 'CANCELLED')")
                    ->update([
                        'item_status' => 'COLLECTED',
                        'picked_up_at' => DB::raw('SYSDATE'),
                    ]);

                DB::connection('oracle')
                    ->table('orders')
                    ->whereRaw('UPPER(order_id) = ?', [$orderId])
                    ->update([
                        'status' => 'collected',
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order marked as picked up.',
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Log::error('RFID pickup failed', [
                'message' => $e->getMessage(),
                'order_id' => $request->input('order_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
