<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $oem_id
 * @property string $oem_name
 * @property string $color
 * @property string $text_color
 * @property string $button_previous_backgroud_color
 * @property string $button_previous_text_color
 * @property string $font_name_regular
 * @property string $font_name_light
 * @property string $font_name_bold
 * @property string $motto
 */
class BrandProperty extends Model {

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'brand_property';

    /**
     * @var array
     */
    protected $fillable = ['id', 'oem_id', 'oem_name', 'color', 'text_color', 'button_previous_backgroud_color', 'button_previous_text_color', 'font_name_regular', 'font_name_light', 'font_name_bold', 'motto', 'ecomm_brand_id', 'font_name_semibold', 'font_name_medium'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
