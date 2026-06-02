<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{
    User,
    Company,
};

class AccountHandlerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jltcbCompany = Company::where('name', 'JLTCB')->first();
        if ($jltcbCompany) {
            $jltcbCompany->update([
                'account_handler_id' => 7,
            ]);
        }
        $companies = Company::where('account_handler_id', null)->where('name', '!=', 'JLTCB')->get();
        foreach ($companies as $company) {
            $accountHandler = User::role(['Account Specialist', 'Lead Account Specialist', 'Client Success', 'Lead Client Success'])->inRandomOrder()->first();
            if ($accountHandler) {
                $company->update(['account_handler_id' => $accountHandler->id]);
            }
        }
    }
}
