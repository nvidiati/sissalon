<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketTemplate
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTemplate whereUpdatedAt($value)
 */
class TicketTemplate extends Model
{
    //
}
