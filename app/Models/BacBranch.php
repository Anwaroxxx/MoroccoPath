<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $rank
 * @property int|null $bac_plus_years
 */
#[Fillable(['code', 'name', 'rank', 'bac_plus_years'])]
class BacBranch extends Model
{
    /** @return HasMany<BacBranchAlias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(BacBranchAlias::class);
    }
}
