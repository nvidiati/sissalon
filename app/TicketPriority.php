<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketPriority
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketPriority whereUpdatedAt($value)
 */
class TicketPriority extends Model
{
    //
}
