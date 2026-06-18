<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->size ?? 10;
        $orderBy = $request->orderBy ?? 'id';
        $orderDir = $request->orderDir ?? 'desc';

        $orders = $this->buildQuery($request)
            ->orderBy($orderBy, $orderDir)
            ->paginate($size);

        $orders->getCollection()->transform(fn($order) => $this->listShape($order));

        return response()->json($orders);
    }

    public function export(Request $request)
    {
        $orderBy = $request->orderBy ?? 'id';
        $orderDir = $request->orderDir ?? 'desc';

        $orders = $this->buildQuery($request)
            ->orderBy($orderBy, $orderDir)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('الطلبات');

        $headers = ['رقم الطلب', 'العميل', 'الهاتف', 'هاتف إضافي', 'الفرع', 'نوع الطلب', 'طريقة الدفع', 'الإجمالي', 'الحالة', 'التاريخ'];
        $sheet->fromArray($headers, null, 'A1');

        $rowNum = 2;
        foreach ($orders as $o) {
            $sheet->setCellValue("A{$rowNum}", $o->id);
            $sheet->setCellValue("B{$rowNum}", $o->client_name);
            $sheet->setCellValueExplicit("C{$rowNum}", (string) $o->client_phone, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string) ($o->client_additional_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$rowNum}", $o->branch_name);
            $sheet->setCellValue("F{$rowNum}", $this->typeLabel($o->type));
            $sheet->setCellValue("G{$rowNum}", $this->paymentLabel($o->payment_type));
            $sheet->setCellValue("H{$rowNum}", $o->total_amount);
            $sheet->setCellValue("I{$rowNum}", $this->statusLabel($o->status));
            $sheet->setCellValue("J{$rowNum}", $o->created_at?->format('Y-m-d H:i'));
            $rowNum++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'orders-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.options.values', 'cancelReason']);

        return response()->json($order);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }

    private function buildQuery(Request $request): Builder
    {
        return Order::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->payment_type, fn($q) => $q->where('payment_type', $request->payment_type))
            ->when($request->id, fn($q) => $q->where('id', $request->id))
            ->when($request->client_name, fn($q) => $q->where('client_name', 'like', '%' . $request->client_name . '%'))
            ->when($request->client_phone, function ($q) use ($request) {
                $phone = $request->client_phone;
                $q->where(function ($sub) use ($phone) {
                    $sub->where('client_phone', 'like', '%' . $phone . '%')
                        ->orWhere('client_additional_phone', 'like', '%' . $phone . '%');
                });
            })
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('id', $search)
                        ->orWhere('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_phone', 'like', '%' . $search . '%')
                        ->orWhere('client_additional_phone', 'like', '%' . $search . '%');
                });
            });
    }

    private function listShape(Order $order): array
    {
        return [
            'id'           => $order->id,
            'reference'    => $order->order_reference,
            'type'         => $order->type,
            'client_name'  => $order->client_name,
            'client_phone' => $order->client_phone,
            'client_additional_phone' => $order->client_additional_phone,
            'branch_id'    => $order->branch_id,
            'branch_name'  => $order->branch_name,
            'location_name'=> $order->location_name,
            'payment_type' => $order->payment_type,
            'total_amount' => $order->total_amount,
            'status'       => $order->status,
            'created_at'   => $order->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function typeLabel(?string $type): string
    {
        return ['delivery' => 'توصيل', 'pick_up' => 'استلام'][$type] ?? (string) $type;
    }

    private function paymentLabel(?string $payment): string
    {
        return ['cash' => 'نقدي', 'visa' => 'فيزا'][$payment] ?? (string) $payment;
    }

    private function statusLabel(?string $status): string
    {
        return ['pending' => 'قيد الانتظار', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'][$status] ?? (string) $status;
    }
}
