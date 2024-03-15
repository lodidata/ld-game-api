<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;

class CurrencyModel extends Model
{
    protected $table = 'currency';

    protected $primaryKey = 'id';
}