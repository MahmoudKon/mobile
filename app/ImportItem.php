<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportItem extends Model
{
    protected $table = 'imports_item';
    protected $guarded = ['id'];
    public $timestamps = false;
}
