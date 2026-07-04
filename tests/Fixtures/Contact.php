<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $guarded = [];

    public $timestamps = false;
}
