<?php

use Illuminate\Database\Seeder;
use App\Models\BrandProperty;

class BrandPropertyTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        BrandProperty::firstOrcreate(array('oem_id' => 'toyo',
            'oem_name' => 'TOYOTA',
            'color' => '#D71921',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'ToyotaType-Regular.ttf',
            'font_name_light' => 'ToyotaType-Light.ttf',
            'font_name_bold' => 'ToyotaType-Bold.ttf',
            'motto' => 'let\'s go places.',
            'ecomm_brand_id'=>33
        ));
        
        BrandProperty::firstOrcreate(array('oem_id' => 'nna',
            'oem_name' => 'NISSAN',
            'color' => '#C11532',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'OpenSans.ttf',
            'font_name_light' => 'OpenSans-Light.ttf',
            'font_name_bold' => 'OpenSans-Bold.ttf',
            'motto' => 'innovation that excites',
            'ecomm_brand_id'=>34
        ));        
        BrandProperty::firstOrcreate(array('oem_id' => 'audi',
            'oem_name' => 'AUDI',
            'color' => '#bb0a30',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'AudiType-Normal.otf',
            'font_name_light' => 'AudiTypeScreen-Light.ttf',
            'font_name_bold' => 'AudiType-Bold.otf',
            'motto' => 'Truth in Engineering.',
            'ecomm_brand_id'=>35
        ));
        BrandProperty::firstOrcreate(array('oem_id' => 'hndi',
            'oem_name' => 'Hyundai',
            'color' => '#0A2972',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'HyundaiSansHead.otf',
            'font_name_light' => 'HyundaiSansHead-Light.otf',
            'font_name_bold' => 'HyundaiSansHead-Bold.otf',
            'motto' => 'NEW THINKING. NEW POSSIBILITIES.',
            'ecomm_brand_id'=>36
        ));
        BrandProperty::firstOrcreate(array('oem_id' => 'infi',
            'oem_name' => 'INFINITI',
            'color' => '#000000',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#FFFFFF',
            'button_previous_text_color' => '#000000',
            'font_name_regular' => 'InfinitiBrand-Regular.ttf',
            'font_name_light' => 'InfinitiBrand-Light.ttf',
            'font_name_bold' => 'InfinitiBrand-Bold.ttf',
            'motto' => 'Empower the drive™.',
            'ecomm_brand_id'=>37
        ));
        BrandProperty::firstOrcreate(array('oem_id' => 'lexu',
            'oem_name' => 'LEXUS',
            'color' => '#000000',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#FFFFFF',
            'button_previous_text_color' => '#000000',
            'font_name_regular' => 'Nobel-Book.ttf',
            'font_name_light' => 'Nobel-Light.ttf',
            'font_name_bold' => 'Nobel-Bold.otf',
            'motto' => 'The Relentless Pursuit of Perfection.',
            'ecomm_brand_id'=>38
        ));
        
        BrandProperty::firstOrcreate(array('oem_id' => 'mits',
            'oem_name' => 'MITSUBISHI',
            'color' => '#DA291C',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'ProximaNovaA-Regular.otf',
            'font_name_light' => 'ProximaNovaA-Light.otf',
            'font_name_bold' => 'ProximaNovaA-Bold.otf',
            'motto' => 'Drive@Earth',
            'ecomm_brand_id'=>39
        ));        
        BrandProperty::firstOrcreate(array('oem_id' => 'gnrc',
            'oem_name' => 'MOPAR',
            'color' => '#0046ad',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'UniversLTStd-Cn - regular.ttf',
            'font_name_light' => 'TitlingGothicFBNarrow-Light.otf',
            'font_name_bold' => 'TitlingGothicFBNarrow-Medium.otf',
            'motto' => 'Motor & Parts.',
            'ecomm_brand_id'=>40
        ));
        BrandProperty::firstOrcreate(array('oem_id' => 'wagen',
            'oem_name' => 'VOLKSWAGEN',
            'color' => '#004C97',
            'text_color' => '#FFFFFF',
            'button_previous_backgroud_color' => '#000000',
            'button_previous_text_color' => '#FFFFFF',
            'font_name_regular' => 'VWHead-Italic.otf',
            'font_name_light' => 'VWHead-Light.otf',
            'font_name_bold' => 'VWHead-ExtraBold.otf',
            'motto' => 'Das Auto',
            'ecomm_brand_id'=>41
        ));        
    }
    
}
