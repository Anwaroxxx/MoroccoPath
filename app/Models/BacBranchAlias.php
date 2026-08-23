<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bac_branch_id
 * @property string $alias
 */
#[Fillable(['bac_branch_id', 'alias'])]
class BacBranchAlias extends Model
{
    /** @return BelongsTo<BacBranch, $this> */
    public function bacBranch(): BelongsTo
    {
        return $this->belongsTo(BacBranch::class);
    }
}
