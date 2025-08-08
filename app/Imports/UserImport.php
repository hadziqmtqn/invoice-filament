<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class UserImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     * @throws Throwable
     */
    public function collection(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            foreach ($collection as $row) {
                // Assuming the first row is the header
                if ($row->isEmpty()) {
                    continue;
                }

                $user = new User();
                $user->name = $row['name'];
                $user->email = $row['email'];
                $user->password = Hash::make($row['password']);
                $user->save();

                $user->assignRole('user'); // Assigning a default role

                $userProfile = new UserProfile();
                $userProfile->user_id = $user->id;
                $userProfile->phone = $row['phone'];
                $userProfile->save();
            }
        });
    }
}
