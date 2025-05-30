<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    // Off fillable rows
    protected $guarded = false;

    public function accesses() {
        return $this->hasMany(Accesses::class);
    }
}
