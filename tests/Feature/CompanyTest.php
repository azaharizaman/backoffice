<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_company()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'description' => 'A test company',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_companies', [
            'name' => 'Test Company',
            'code' => 'TEST',
            'description' => 'A test company',
            'is_active' => true,
        ]);

        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('TEST', $company->code);
        $this->assertTrue($company->is_active);
        $this->assertTrue($company->isActive());
    }

    /** @test */
    public function it_can_create_company_hierarchy()
    {
        $parentCompany = Company::create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
            'is_active' => true,
        ]);

        $childCompany = Company::create([
            'name' => 'Child Company',
            'code' => 'CHILD',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $this->assertEquals($parentCompany->id, $childCompany->parent_company_id);
        $this->assertTrue($parentCompany->childCompanies->contains($childCompany));
        $this->assertEquals($parentCompany->id, $childCompany->parentCompany->id);
    }

    /** @test */
    public function it_can_get_root_company()
    {
        $rootCompany = Company::create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $level1Company = Company::create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $this->assertEquals($rootCompany->id, $level2Company->rootCompany()->id);
        $this->assertTrue($level2Company->isDescendantOf($rootCompany));
        $this->assertTrue($rootCompany->isAncestorOf($level2Company));
    }

    /** @test */
    public function it_can_get_all_descendants()
    {
        $parentCompany = Company::create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
            'is_active' => true,
        ]);

        $child1 = Company::create([
            'name' => 'Child 1',
            'code' => 'CHILD1',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $child2 = Company::create([
            'name' => 'Child 2',
            'code' => 'CHILD2',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $grandchild = Company::create([
            'name' => 'Grandchild',
            'code' => 'GRANDCHILD',
            'parent_company_id' => $child1->id,
            'is_active' => true,
        ]);

        $descendants = $parentCompany->allChildCompanies();
        
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    /** @test */
    public function it_can_get_ancestors()
    {
        $rootCompany = Company::create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $level1Company = Company::create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $ancestors = $level2Company->allParentCompanies();
        
        $this->assertCount(2, $ancestors);
        $this->assertTrue($ancestors->contains('id', $level1Company->id));
        $this->assertTrue($ancestors->contains('id', $rootCompany->id));
    }

    /** @test */
    public function it_can_check_if_company_is_root()
    {
        $rootCompany = Company::create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $childCompany = Company::create([
            'name' => 'Child Company',
            'code' => 'CHILD',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $this->assertTrue($rootCompany->isRoot());
        $this->assertFalse($childCompany->isRoot());
    }

    /** @test */
    public function it_can_check_if_company_is_leaf()
    {
        $parentCompany = Company::create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
            'is_active' => true,
        ]);

        $childCompany = Company::create([
            'name' => 'Child Company',
            'code' => 'CHILD',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $this->assertFalse($parentCompany->isLeaf());
        $this->assertTrue($childCompany->isLeaf());
    }

    /** @test */
    public function it_can_get_hierarchy_depth()
    {
        $rootCompany = Company::create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $level1Company = Company::create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $this->assertEquals(0, $rootCompany->getDepth());
        $this->assertEquals(1, $level1Company->getDepth());
        $this->assertEquals(2, $level2Company->getDepth());
    }

    /** @test */
    public function it_can_get_hierarchy_path()
    {
        $rootCompany = Company::create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $level1Company = Company::create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $path = $level2Company->getPath();
        
        $this->assertCount(3, $path);
        $this->assertEquals($rootCompany->id, $path[0]->id);
        $this->assertEquals($level1Company->id, $path[1]->id);
        $this->assertEquals($level2Company->id, $path[2]->id);
    }

    /** @test */
    public function it_can_get_siblings()
    {
        $parentCompany = Company::create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
            'is_active' => true,
        ]);

        $child1 = Company::create([
            'name' => 'Child 1',
            'code' => 'CHILD1',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $child2 = Company::create([
            'name' => 'Child 2',
            'code' => 'CHILD2',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $child3 = Company::create([
            'name' => 'Child 3',
            'code' => 'CHILD3',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $siblings = $child1->getSiblings();
        
        $this->assertCount(2, $siblings);
        $this->assertTrue($siblings->contains('id', $child2->id));
        $this->assertTrue($siblings->contains('id', $child3->id));
        $this->assertFalse($siblings->contains('id', $child1->id));
    }

    /** @test */
    public function it_can_scope_root_companies()
    {
        $rootCompany1 = Company::create([
            'name' => 'Root Company 1',
            'code' => 'ROOT1',
            'is_active' => true,
        ]);

        $rootCompany2 = Company::create([
            'name' => 'Root Company 2',
            'code' => 'ROOT2',
            'is_active' => true,
        ]);

        $childCompany = Company::create([
            'name' => 'Child Company',
            'code' => 'CHILD',
            'parent_company_id' => $rootCompany1->id,
            'is_active' => true,
        ]);

        $rootCompanies = Company::root()->get();
        
        $this->assertCount(2, $rootCompanies);
        $this->assertTrue($rootCompanies->contains('id', $rootCompany1->id));
        $this->assertTrue($rootCompanies->contains('id', $rootCompany2->id));
        $this->assertFalse($rootCompanies->contains('id', $childCompany->id));
    }

    /** @test */
    public function it_can_scope_active_companies()
    {
        $activeCompany = Company::create([
            'name' => 'Active Company',
            'code' => 'ACTIVE',
            'is_active' => true,
        ]);

        $inactiveCompany = Company::create([
            'name' => 'Inactive Company',
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $activeCompanies = Company::active()->get();
        
        $this->assertCount(1, $activeCompanies);
        $this->assertTrue($activeCompanies->contains('id', $activeCompany->id));
        $this->assertFalse($activeCompanies->contains('id', $inactiveCompany->id));
    }

    /** @test */
    public function it_requires_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Company::create([
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_have_nullable_code()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $this->assertNull($company->code);
    }

    /** @test */
    public function it_can_have_nullable_description()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $this->assertNull($company->description);
    }

    /** @test */
    public function it_defaults_to_active()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
        ]);

        $this->assertTrue($company->is_active);
    }
}