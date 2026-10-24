<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;
use App\Support\StarterData;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function company(string $name = 'PT Contoh'): Company
    {
        $company = Company::create(['name' => $name]);
        StarterData::seed($company);

        return $company;
    }

    protected function member(Company $company, Role $role = Role::Manajer, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->memberships()->create(['company_id' => $company->id, 'role' => $role, 'is_active' => true]);

        return $user;
    }

    protected function useCompany(Company $company): void
    {
        app(CurrentCompany::class)->set($company);
    }
}
