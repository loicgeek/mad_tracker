<?php
// app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = ['nom', 'pays', 'contact_nom', 'contact_email', 'contact_phone', 'notes'];

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }
}
