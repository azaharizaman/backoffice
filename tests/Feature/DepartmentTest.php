<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\OfficeType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_department()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'IT Department',
            'code' => 'IT',
            'description' => 'Information Technology Department',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_departments', [
            'name' => 'IT Department',
            'code' => 'IT',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $this->assertEquals('IT Department', $department->name);
        $this->assertEquals($office->id, $department->office_id);
    }

    /** @test */
    public function it_belongs_to_office()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'HR Department',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $this->assertEquals($office->id, $department->office->id);
        $this->assertEquals('Test Office', $department->office->name);
    }

    /** @test */
    public function it_can_create_department_hierarchy()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $parentDepartment = Department::factory()->create([
            'name' => 'Technology',
            'code' => 'TECH',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $childDepartment = Department::factory()->create([
            'name' => 'Software Development',
            'code' => 'SOFTDEV',
            'office_id' => $office->id,
            'parent_department_id' => $parentDepartment->id,
            'is_active' => true,
        ]);

        $this->assertEquals($parentDepartment->id, $childDepartment->parent_department_id);
        $this->assertTrue($parentDepartment->childDepartments->contains($childDepartment));
        $this->assertEquals($parentDepartment->id, $childDepartment->parentDepartment->id);
    }

    /** @test */
    public function it_can_get_all_departments_in_hierarchy()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $rootDepartment = Department::factory()->create([
            'name' => 'Technology',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $child1 = Department::factory()->create([
            'name' => 'Software Development',
            'office_id' => $office->id,
            'parent_department_id' => $rootDepartment->id,
            'is_active' => true,
        ]);

        $child2 = Department::factory()->create([
            'name' => 'Quality Assurance',
            'office_id' => $office->id,
            'parent_department_id' => $rootDepartment->id,
            'is_active' => true,
        ]);

        $grandchild = Department::factory()->create([
            'name' => 'Frontend Development',
            'office_id' => $office->id,
            'parent_department_id' => $child1->id,
            'is_active' => true,
        ]);

        $descendants = $rootDepartment->allChildDepartments();
        
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    /** @test */
    public function it_can_scope_by_office()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office1 = Office::factory()->create([
            'name' => 'Office 1',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $office2 = Office::factory()->create([
            'name' => 'Office 2',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $dept1 = Department::factory()->create([
            'name' => 'IT Department',
            'office_id' => $office1->id,
            'is_active' => true,
        ]);

        $dept2 = Department::factory()->create([
            'name' => 'HR Department',
            'office_id' => $office2->id,
            'is_active' => true,
        ]);

        $office1Departments = Department::byOffice($office1->id)->get();
        
        $this->assertCount(1, $office1Departments);
        $this->assertTrue($office1Departments->contains('id', $dept1->id));
        $this->assertFalse($office1Departments->contains('id', $dept2->id));
    }

    /** @test */
    public function it_can_scope_active_departments()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $activeDepartment = Department::factory()->create([
            'name' => 'Active Department',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $inactiveDepartment = Department::factory()->create([
            'name' => 'Inactive Department',
            'office_id' => $office->id,
            'is_active' => false,
        ]);

        $activeDepartments = Department::active()->get();
        
        $this->assertCount(1, $activeDepartments);
        $this->assertTrue($activeDepartments->contains('id', $activeDepartment->id));
        $this->assertFalse($activeDepartments->contains('id', $inactiveDepartment->id));
    }

    /** @test */
    public function it_requires_name_and_office()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Department::factory()->create([
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_defaults_to_active()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'Test Department',
            'office_id' => $office->id,
        ]);

        $this->assertTrue($department->is_active);
    }
}