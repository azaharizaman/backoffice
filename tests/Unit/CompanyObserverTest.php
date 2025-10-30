<?php

namespace AzahariZaman\BackOffice\Tests\Unit;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Exceptions\CircularReferenceException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_circular_reference_when_creating()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Cannot set parent: Circular reference detected');

        Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $company->id,
            'is_active' => true,
        ]);

        // This should fail when the observer detects the circular reference
        $company->update(['parent_company_id' => $company->id]);
    }

    /** @test */
    public function it_prevents_circular_reference_when_updating()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'is_active' => true,
        ]);

        $childCompany = Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $grandChildCompany = Company::factory()->create([
            'name' => 'Grandchild Company',
            'parent_company_id' => $childCompany->id,
            'is_active' => true,
        ]);

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Cannot set parent: Circular reference detected');

        // Try to make parent company a child of its own descendant
        $parentCompany->update(['parent_company_id' => $grandChildCompany->id]);
    }

    /** @test */
    public function it_allows_valid_hierarchy_changes()
    {
        $company1 = Company::factory()->create([
            'name' => 'Company 1',
            'is_active' => true,
        ]);

        $company2 = Company::factory()->create([
            'name' => 'Company 2',
            'is_active' => true,
        ]);

        $childCompany = Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $company1->id,
            'is_active' => true,
        ]);

        // This should work fine - moving to a different parent
        $childCompany->update(['parent_company_id' => $company2->id]);

        $this->assertEquals($company2->id, $childCompany->fresh()->parent_company_id);
    }

    /** @test */
    public function it_allows_removing_parent()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'is_active' => true,
        ]);

        $childCompany = Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        // This should work fine - removing parent makes it a root company
        $childCompany->update(['parent_company_id' => null]);

        $this->assertNull($childCompany->fresh()->parent_company_id);
        $this->assertTrue($childCompany->fresh()->isRoot());
    }
}