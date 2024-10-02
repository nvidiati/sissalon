<?php

namespace App\Exports;

use DB;
use App\User;
use App\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportUsers implements FromCollection, WithHeadings
{
    public $startDate;
    public $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function headings(): array
    {
        return [
            'Id',
            'Customer Name',
            'Email',
            'Phone',
            'Total Bookings',
            'Registered Date',
        ];

    }

    public function collection()
    {

        $customer = User::withoutGlobalScopes()->with('customerBookings')->has('customerBookings')
            ->whereHas('customerBookings', function ($query) {
                $query->whereDate('date_time', '>=', Carbon::createFromFormat('Y-m-d', $this->startDate))
                    ->whereDate('date_time', '<=', Carbon::createFromFormat('Y-m-d', $this->endDate));
            })
            ->get();
            
        $exportArray = [];

        foreach($customer as $row)
        {
            $exportArray[] = [
                'Id' => $row->id,
                'Customer Name' => $row->name,
                'Email' => $row->email,
                'Phone' => $row->mobile,
                'Total Bookings' => $row->customerBookings->count(),
                'Registered Date' => $row->created_at->format('Y-m-d'),
            ];
        }

        $userData = collect($exportArray);

        return collect($userData);
    }

}
