<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accesses extends Model
{
    // Off fillable rows
    protected $guarded = false;

    public function users() {
        return $this->belongsTo(User::class);
    }
    public function files() {
        return $this->belongsTo(Files::class);
    }
}
