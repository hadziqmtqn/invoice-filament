<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $user = User::where('email', '!=', 'superadmin@bkn.my.id')
            ->pluck('id');

        $date = $this->faker->dateTimeBetween('-4 months')->format('Y-m-d');

        return [
            'user_id' => $user->random(),
            'title' => $this->faker->word(),
            'date' => $date,
            'due_date' => Carbon::parse($date)->addDays(7)->format('Y-m-d'),
            'note' => $this->faker->word(),
            'status' => 'paid',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
