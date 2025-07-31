<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Application extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
    ];

    // TODO More fields can be added as needed
    protected function logo(): Attribute
    {
        return Attribute::make(fn() => $this->hasMedia('logo') ? $this->getFirstTemporaryUrl(Carbon::now()->addHour(), 'logo') : url('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=128&background=00bb00&color=ffffff&rounded=true'));
    }

    protected function favicon(): Attribute
    {
        return Attribute::make(fn() => $this->hasMedia('favicon') ? $this->getFirstTemporaryUrl(Carbon::now()->addHour(), 'favicon') : url('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&size=64&background=00bb00&color=ffffff&rounded=true'));
    }
}
