<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SocialAuthSetting
 *
 * @property int $id
 * @property string|null $google_client_id
 * @property string|null $google_secret_id
 * @property string $google_status
 * @property string|null $facebook_client_id
 * @property string|null $facebook_secret_id
 * @property string $facebook_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereFacebookClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereFacebookSecretId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereFacebookStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereGoogleClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereGoogleSecretId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereGoogleStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SocialAuthSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SocialAuthSetting extends Model
{
    //
}
