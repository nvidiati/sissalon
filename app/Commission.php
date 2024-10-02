<?php

namespace App;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Commission
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $currency_id
 * @property float $total_amount
 * @property float $commission_amount
 * @property float $deposit_amount
 * @property float $pending_amount
 * @property string|null $gateway
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_on
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Company|null $company
 * @property-read \App\Currency|null $currency
 * @method static \Illuminate\Database\Eloquent\Builder|Commission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Commission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Commission query()
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereDepositAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereGateway($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission wherePaidOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission wherePendingAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Commission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Commission extends Model
{
    protected $table = 'commissions';

    protected $dates = ['paid_on'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

}
