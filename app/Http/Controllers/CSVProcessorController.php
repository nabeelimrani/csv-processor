<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class CSVProcessorController extends Controller
{
    public function saveToDatabase()
    {
        $fileContent = File::get(public_path('csv/backend task.csv'));
        $csvData = array_map('str_getcsv', explode("\n", $fileContent));

        $headers = array_shift($csvData);

        foreach ($csvData as $index => $row) {
            if (count($row) !== count($headers) || in_array(null, $row, true)) {
                continue;
            }

            $data = array_combine($headers, $row);

            if (!isset($data['Email_address'], $data['Order_Date'], $data['product_qty'])) {
                continue;
            }

            $orderDate = Carbon::parse($data['Order_Date'])->format('Y-m-d H:i:s');

            $existingCustomer = CustomerOrder::where('customer_email', $data['Email_address'])->first();

            if (!$existingCustomer) {
                CustomerOrder::create([
                    'customer_email' => $data['Email_address'],
                    'first_order_date' => $orderDate,
                    'last_order_date' => $orderDate,
                    'total_orders' => 1,
                    'total_product_quantities' => $data['product_qty'],
                ]);
            } else {
                $existingCustomer->update([
                    'last_order_date' => $orderDate,
                    'total_orders' => $existingCustomer->total_orders + 1,
                    'total_product_quantities' => $existingCustomer->total_product_quantities + $data['product_qty'],
                ]);
            }
        }

        return response()->json(['message' => 'Data saved to database successfully']);
    }

    public function generateNewCsv()
    {
        $fileContent = File::get(public_path('csv/backend task.csv'));
        $csvData = array_map('str_getcsv', explode("\n", $fileContent));
        $headers = array_shift($csvData);

        $uniqueEmails = CustomerOrder::distinct()->pluck('customer_email');

        $resultData = [];
        foreach ($uniqueEmails as $email) {
            $customerData = CustomerOrder::where('customer_email', $email)->get();
            $firstOrderDate = $customerData->min('order_date');
            $lastOrderDate = $customerData->max('order_date');
            $daysDifference = Carbon::parse($lastOrderDate)->diffInDays(Carbon::parse($firstOrderDate));
            $totalOrders = $customerData->count();
            $totalProductQuantities = $customerData->sum('product_quantity');

            $resultData[] = [
                'Customer Email' => $email,
                'First Order Date' => $firstOrderDate,
                'Last Order Date' => $lastOrderDate,
                'Days Difference' => $daysDifference,
                'Total Number of Orders' => $totalOrders,
                'Total Number of Product Quantities' => $totalProductQuantities,
            ];
        }

        $handle = fopen(public_path('csv/new.csv'), 'w');
        fputcsv($handle, ['Customer Email', 'First Order Date', 'Last Order Date', 'Days Difference', 'Total Number of Orders', 'Total Number of Product Quantities']);
        foreach ($resultData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return response()->json(['message' => 'New CSV file generated successfully']);
    }
}
