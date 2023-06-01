<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientTransaction extends Model
{
    //
    protected $table = 'client_transaction';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public static function boot()
    {
        parent::boot();
        
        self::creating(function ($model) {
            $model->unique_columns = "{$model->client_id}-{$model->amount}-{$model->date_time}";
        });

    }
}
