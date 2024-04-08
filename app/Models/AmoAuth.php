<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель AmoAuth для работы с аутентификационными данными AmoCRM.
 *
 * @property int $account_id Идентификатор аккаунта
 * @property string $access_token Токен доступа
 * @property string $refresh_token Токен для обновления
 * @property \Carbon\Carbon $expires Время истечения токена доступа
 * @property string $base_domain Базовый домен
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 */
class AmoAuth extends Model
{
    use HasFactory;

    protected $table = 'amo_auths';

    protected $primaryKey = 'account_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'account_id',
        'access_token',
        'refresh_token',
        'expires',
        'base_domain',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'expires' => 'datetime',
    ];
}
