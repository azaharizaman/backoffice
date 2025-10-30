<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use AzahariZaman\BackOffice\Traits\HasHierarchy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Company Model
 * 
 * Represents a company entity that can have parent-child relationships.
 * One parent company can have many child companies.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int|null $parent_company_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Company|null $parentCompany
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Company> $childCompanies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Office> $offices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 */
class Company extends Model
{
    use HasFactory, SoftDeletes, HasHierarchy;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_company_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent company.
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get the child companies.
     */
    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the offices for this company.
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    /**
     * Get the departments for this company.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get the root company (top-level parent).
     */
    public function rootCompany(): Company
    {
        return $this->getRoot();
    }

    /**
     * Get all descendant companies.
     */
    public function allChildCompanies()
    {
        return $this->getDescendants();
    }

    /**
     * Get all ancestor companies.
     */
    public function allParentCompanies()
    {
        return $this->getAncestors();
    }

    /**
     * Check if company is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root companies (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_company_id');
    }
}