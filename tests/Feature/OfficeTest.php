<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\OfficeType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_office()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'description' => 'Main office',
            'is_active' => true,
        ]);

        $office = Office::create([
            'name' => 'Main Office',
            'code' => 'MAIN',
            'description' => 'Main office location',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_offices', [
            'name' => 'Main Office',
            'code' => 'MAIN',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $this->assertEquals('Main Office', $office->name);
        $this->assertEquals($company->id, $office->company_id);
        $this->assertEquals($officeType->id, $office->office_type_id);
    }

    /** @test */
    public function it_belongs_to_company()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $this->assertEquals($company->id, $office->company->id);
        $this->assertEquals('Test Company', $office->company->name);
    }

    /** @test */
    public function it_belongs_to_office_type()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Regional Office',
            'code' => 'REGIONAL',
            'is_active' => true,
        ]);

        $office = Office::create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $this->assertEquals($officeType->id, $office->officeType->id);
        $this->assertEquals('Regional Office', $office->officeType->name);
    }

    /** @test */
    public function it_can_create_office_hierarchy()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $parentOffice = Office::create([
            'name' => 'Parent Office',
            'code' => 'PARENT',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $childOffice = Office::create([
            'name' => 'Child Office',
            'code' => 'CHILD',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'parent_office_id' => $parentOffice->id,
            'is_active' => true,
        ]);

        $this->assertEquals($parentOffice->id, $childOffice->parent_office_id);
        $this->assertTrue($parentOffice->childOffices->contains($childOffice));
        $this->assertEquals($parentOffice->id, $childOffice->parentOffice->id);
    }

    /** @test */
    public function it_can_get_root_office()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $rootOffice = Office::create([
            'name' => 'Root Office',
            'code' => 'ROOT',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $level1Office = Office::create([
            'name' => 'Level 1 Office',
            'code' => 'L1',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'parent_office_id' => $rootOffice->id,
            'is_active' => true,
        ]);

        $level2Office = Office::create([
            'name' => 'Level 2 Office',
            'code' => 'L2',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'parent_office_id' => $level1Office->id,
            'is_active' => true,
        ]);

        $this->assertEquals($rootOffice->id, $level2Office->rootOffice()->id);
        $this->assertTrue($level2Office->isDescendantOf($rootOffice));
        $this->assertTrue($rootOffice->isAncestorOf($level2Office));
    }

    /** @test */
    public function it_can_scope_active_offices()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $activeOffice = Office::create([
            'name' => 'Active Office',
            'code' => 'ACTIVE',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $inactiveOffice = Office::create([
            'name' => 'Inactive Office',
            'code' => 'INACTIVE',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => false,
        ]);

        $activeOffices = Office::active()->get();
        
        $this->assertCount(1, $activeOffices);
        $this->assertTrue($activeOffices->contains('id', $activeOffice->id));
        $this->assertFalse($activeOffices->contains('id', $inactiveOffice->id));
    }

    /** @test */
    public function it_can_scope_by_company()
    {
        $company1 = Company::create([
            'name' => 'Company 1',
            'code' => 'COMP1',
            'is_active' => true,
        ]);

        $company2 = Company::create([
            'name' => 'Company 2',
            'code' => 'COMP2',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office1 = Office::create([
            'name' => 'Office 1',
            'company_id' => $company1->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $office2 = Office::create([
            'name' => 'Office 2',
            'company_id' => $company2->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $company1Offices = Office::byCompany($company1->id)->get();
        
        $this->assertCount(1, $company1Offices);
        $this->assertTrue($company1Offices->contains('id', $office1->id));
        $this->assertFalse($company1Offices->contains('id', $office2->id));
    }

    /** @test */
    public function it_can_scope_by_office_type()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $branchType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $hqType = OfficeType::create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $branchOffice = Office::create([
            'name' => 'Branch Office',
            'company_id' => $company->id,
            'office_type_id' => $branchType->id,
            'is_active' => true,
        ]);

        $hqOffice = Office::create([
            'name' => 'HQ Office',
            'company_id' => $company->id,
            'office_type_id' => $hqType->id,
            'is_active' => true,
        ]);

        $branchOffices = Office::byType($branchType->id)->get();
        
        $this->assertCount(1, $branchOffices);
        $this->assertTrue($branchOffices->contains('id', $branchOffice->id));
        $this->assertFalse($branchOffices->contains('id', $hqOffice->id));
    }

    /** @test */
    public function it_requires_name_and_company_and_office_type()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Office::create([
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_have_nullable_code()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $this->assertNull($office->code);
    }

    /** @test */
    public function it_defaults_to_active()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
        ]);

        $this->assertTrue($office->is_active);
    }
}