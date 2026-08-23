<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name', 'description'])]
class Skill extends Model
{
    /** @return BelongsToMany<Program, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_skills');
    }
}
