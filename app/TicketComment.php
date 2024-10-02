<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TicketComment
 *
 * @property-read mixed $files
 * @property-read \App\Ticket $ticket
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketComment whereUserId($value)
 */
class TicketComment extends Model
{
    protected $wiht = ['user'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function getFilesAttribute($value)
    {
        if (is_array(json_decode($value, true))) {
            return json_decode($value, true);
        }

        return $value;
    }

}
