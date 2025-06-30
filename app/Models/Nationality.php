<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Nationality extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'nom',
    ];

    // Relations
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('is_active', true);
    }

    // Scopes 

    public function scopeByCode(Builder $query, string $code): void
    {
        $query->where('code', $code);
    }

    // Accessors
    public function getEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
    }

    public function getActiveEmployeesCountAttribute(): int
    {
        return $this->activeEmployees()->count();
    }


}
