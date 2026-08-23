<?php

namespace App\Models;

use App\Enums\EducationLevelCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rung of the normalized education ladder. `rank` gives a strict total
 * order so "at least BAC_PLUS_2" is a database-backed comparison.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property EducationLevelCategory $category
 * @property int $rank
 * @property int|null $bac_plus_years
 */
#[Fillable(['code', 'name', 'category', 'rank', 'bac_plus_years'])]
class EducationLevel extends Model
{
    protected function casts(): array
    {
        return [
            'category' => EducationLevelCategory::class,
            'rank' => 'integer',
            'bac_plus_years' => 'integer',
        ];
    }

    /** @return HasMany<Qualification, $this> */
    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class, 'equivalent_level_id');
    }
}
