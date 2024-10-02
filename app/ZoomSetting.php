<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ZoomSetting
 *
 * @property int $id
 * @property int|null $company_id
 * @property string|null $api_key
 * @property string|null $secret_key
 * @property string|null $purchase_code
 * @property string $meeting_app
 * @property string|null $supported_until
 * @property string $enable_zoom
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereEnableZoom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereMeetingApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting wherePurchaseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereSecretKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereSupportedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ZoomSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ZoomSetting extends Model
{
    protected $fillable = ['api_key', 'secret_key', 'purchase_code', 'supported_until', 'purchase_code', 'meeting_app'];

}
