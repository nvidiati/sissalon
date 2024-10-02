<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RoleUser
 *
 * @property int $role_id
 * @property int $user_id
 * @property string $user_type
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereUserType($value)
 * @mixin \Eloquent
 */
class RoleUser extends Model
{
    protected $table = 'role_user';
}
