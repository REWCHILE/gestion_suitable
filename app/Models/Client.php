<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'empresa',
        'contacto_nombre',
        'email',
        'telefono',
        'cargo',
        'region_comuna',
        'tamano_equipo',
        'estado',
        'notas',
        'fecha_tallaje',
        'monto_cotizacion',
        'ultimo_envio_tipo',
        'ultimo_envio_fecha',
        'asignado_a'
    ];

    protected $casts = [
        'tamano_equipo' => 'integer',
        'monto_cotizacion' => 'decimal:2',
        'fecha_tallaje' => 'date',
        'ultimo_envio_fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'group_members', 'client_id', 'group_id')
            ->withTimestamps();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'client_id');
    }
}
