<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Support\InvoicePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceWebController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['payment', 'collectionSlot'])
            ->where('customer_id', Auth::id())
            ->orderByDesc('order_date')
            ->limit(100)
            ->get();

        $filtered = InvoicePresenter::filterOrdersForUser(
            $orders,
            $request->string('q')->trim()->toString() ?: null,
            $request->string('from')->trim()->toString() ?: null,
            $request->string('to')->trim()->toString() ?: null,
        );

        return view('invoices.index', [
            'orders' => $filtered,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
        ]);
    }

    public function show(string $orderId): View
    {
        $order = $this->orderService->findForCustomer(Auth::user(), $orderId);
        $invoice = InvoicePresenter::forOrder($order, Auth::user());

        return view('invoices.show', [
            'order' => $order,
            'invoice' => $invoice,
            'company' => config('grocerygo.company'),
        ]);
    }

    public function export(string $orderId): StreamedResponse
    {
        $order = $this->orderService->findForCustomer(Auth::user(), $orderId);
        $invoice = InvoicePresenter::forOrder($order, Auth::user());
        $filename = 'invoice-'.$order->order_id.'.csv';

        return response()->streamDownload(function () use ($invoice, $order): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['Invoice ID', $invoice['invoice_id']]);
            fputcsv($out, ['Order ID', $invoice['order_id']]);
            fputcsv($out, ['Customer ID', $invoice['customer_id']]);
            fputcsv($out, ['Order date', $invoice['order_date']]);
            fputcsv($out, ['Pickup date', $invoice['pickup_date']]);
            fputcsv($out, []);
            fputcsv($out, ['Product', 'Order ID', 'Customer ID', 'Qty', 'Price', 'Total']);
            foreach ($invoice['lines'] as $line) {
                fputcsv($out, [
                    $line['product_name'],
                    $line['order_id'],
                    $line['customer_id'],
                    $line['quantity'],
                    number_format($line['unit_price'], 2, '.', ''),
                    number_format($line['line_total'], 2, '.', ''),
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Discount', number_format($invoice['discount'], 2, '.', '')]);
            fputcsv($out, ['Total', number_format($invoice['total'], 2, '.', '')]);
            fputcsv($out, ['Status', $invoice['payment_status']]);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(string $orderId): View
    {
        $order = $this->orderService->findForCustomer(Auth::user(), $orderId);
        $invoice = InvoicePresenter::forOrder($order, Auth::user());

        return view('invoices.print', [
            'order' => $order,
            'invoice' => $invoice,
            'company' => config('grocerygo.company'),
        ]);
    }
}
