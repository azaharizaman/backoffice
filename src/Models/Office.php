<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use AzahariZaman\BackOffice\Traits\HasHierarchy;
use AzahariZaman\BackOffice\Enums\OfficeType;

/**
 * Office Model
 * 
 * Represents a physical office structure that can have hierarchical relationships.
 * Offices belong to companies and can have multiple types.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $company_id
 * @property int|null $parent_office_id
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Company $company
 * @property-read Office|null $parentOffice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Office> $childOffices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OfficeType> $officeTypes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Staff> $staff
 */
class Office extends Model
{
    use HasFactory, SoftDeletes, HasHierarchy;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_offices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'company_id',
        'parent_office_id',
        'address',
        'phone',
        'email',
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
     * Get the company that owns this office.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent office.
     */
    public function parentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_office_id');
    }

    /**
     * Get the child offices.
     */
    public function childOffices(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_office_id');
    }

    /**
     * Get the office types associated with this office.
     */
    public function officeTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            OfficeType::class,
            'backoffice_office_office_type',
            'office_id',
            'office_type_id'
        );
    }

    /**
     * Get the staff assigned to this office.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get the root office (top-level parent).
     */
    public function rootOffice(): Office
    {
        return $this->getRoot();
    }

    /**
     * Get all descendant offices.
     */
    public function allChildOffices()
    {
        return $this->getDescendants();
    }

    /**
     * Get all ancestor offices.
     */
    public function allParentOffices()
    {
        return $this->getAncestors();
    }

    /**
     * Check if office is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): ?string
    {
        return $this->address;
    }

    /**
     * Scope to get only active offices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root offices (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_office_id');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by office type.
     */
    public function scopeWithType($query, $officeTypeId)
    {
        return $query->whereHas('officeTypes', function ($q) use ($officeTypeId) {
            $q->where('office_type_id', $officeTypeId);
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \AzahariZaman\BackOffice\Database\Factories\OfficeFactory
    {
        return \AzahariZaman\BackOffice\Database\Factories\OfficeFactory::new();
    }
}