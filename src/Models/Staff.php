<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Staff Model
 * 
 * Represents staff/employees that can belong to offices and/or departments.
 * Staff can belong to one office and/or one department, and can be part of multiple units.
 * 
 * @property int $id
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property int|null $office_id
 * @property int|null $department_id
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $hire_date
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Office|null $office
 * @property-read Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Unit> $units
 */
class Staff extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_staff';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'office_id',
        'department_id',
        'position',
        'hire_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['full_name'];

    /**
     * Get the office that this staff belongs to.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the department that this staff belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the units this staff belongs to.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(
            Unit::class,
            'backoffice_staff_unit',
            'staff_id',
            'unit_id'
        );
    }

    /**
     * Get the staff's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the company through office or department.
     */
    public function getCompany(): ?Company
    {
        if ($this->office) {
            return $this->office->company;
        }
        
        if ($this->department) {
            return $this->department->company;
        }
        
        return null;
    }

    /**
     * Check if staff is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if staff belongs to an office.
     */
    public function hasOffice(): bool
    {
        return !is_null($this->office_id);
    }

    /**
     * Check if staff belongs to a department.
     */
    public function hasDepartment(): bool
    {
        return !is_null($this->department_id);
    }

    /**
     * Scope to get only active staff.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by office.
     */
    public function scopeInOffice($query, $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Scope to filter by department.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by unit.
     */
    public function scopeInUnit($query, $unitId)
    {
        return $query->whereHas('units', function ($q) use ($unitId) {
            $q->where('unit_id', $unitId);
        });
    }

    /**
     * Scope to search by name.
     */
    public function scopeSearchByName($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        });
    }
}