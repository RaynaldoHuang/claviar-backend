<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function resolve(string $name, string $phone): Customer
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return Customer::updateOrCreate(['phone' => $phone], ['name' => $name]);
    }
}
