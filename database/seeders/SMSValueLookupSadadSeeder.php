<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SMSValueLookupSadadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lookupValues = [
            'SadadID' => [
                '001' => 'الإتصالات السعودية',
                '002' => 'الشركة السعودية للكهرباء',
                '003' => 'التعاونية للتأمين',
                '004' => 'مـرافــق',
                '005' => 'مـوبـايـلـي',
                '006' => 'أمـانـة الـمـديـنـة الـمـنـورة',
                '007' => 'مبادرة الحاسب المنزلي',
                '008' => 'قياس',
                '009' => 'انتساب جامعة الملك عبدالعزيز',
                '010' => 'أمانة الرياض',
                '012' => 'مدفوعات خدمات سامبا',
                '013' => 'وزارة التجارة',
                '014' => 'STC المتخصصة',
                '015' => 'خدمات المياه',
                '016' => 'تمويل و بطاقات الأهلي',
                '017' => 'الجامعة العربية المفتوحة',
                '018' => 'أمريكان اكسبريس',
                '019' => 'غرفة جدة',
                '020' => 'هيئة الزكاة و الدخل',
                '021' => 'البريد السعودي',
                '022' => 'الخطوط الجوية العربية السعودية',
                '023' => 'شركة الحمراني للاستثمار التجاري',
                '024' => 'شركة اليسر للإجارة والتمويل',
                '025' => 'أمانة المنطقة الشرقية',
                '026' => 'ناس',
                '027' => 'أمانة العاصمة المقدسة',
                '028' => 'أمانة محافظة جدة',
                '029' => 'خدمات ساب للمدفوعات',
                '030' => 'الهيئة العامة للجمارك',
                '031' => 'مدفوعات البطاقات الائتمانية للبنك الأول',
                '032' => 'أو أس أن',
                '033' => 'جو للاتصالات',
                '034' => 'مباشر',
                '035' => 'كادر المدن الاقتصادية',
                '036' => 'الشـركـة السـعـودية للـمـعـلومات الائتمانية',
                '037' => 'نسما انترنت',
                '038' => 'سعودي نت',
                '039' => 'أوان لبيانات الأسواق',
                '040' => 'صندوق التنمية العقاري',
                '041' => 'أول نت',
                '042' => 'هيئة الاتصالات و تقنية المعلومات',
                '043' => 'أضاحي',
                '044' => 'زين',
                '045' => 'البنك الزراعي العربي السعودي',
                '046' => 'الهيئة الملكية بينبع',
                '047' => 'جريدة الرياض',
                '048' => 'صحارى نت',
                '049' => 'الهيئة السعودية للموصفات و المقاييس والجودة',
                '050' => 'وزارة العمل',
                '051' => 'وزارة الإعلام',
                '052' => 'شركة التيسير العربية',
                '053' => 'فواتير إعلانات واشتراكات جريدة الجزيرة',
                '054' => 'خدمات مصرف الراجحي',
                '055' => 'شركة الاتصالات المتكاملة المحدودة',
                '056' => 'بطاقة فالكون الائتمانية',
                '057' => 'الهيئة العامة للسياحة والآثار',
                '058' => 'بنك التنمية الاجتماعية',
                '059' => 'سيبيريا',
                '060' => 'التامينات الاجتماعية',
                '061' => 'أمانة منطقة حائل',
                '062' => 'امانة تبوك',
                '063' => 'مجموعة تأجير',
                '064' => 'أمانة منطقة جازان',
                '065' => 'شركة عبد اللطيف جميل المتحدة للتمويل',
                '066' => 'نظام الخير الشامل',
                '067' => 'مدفوعات بنك الرياض',
                '068' => 'مدن',
                '069' => 'شركة تمويلي العالمية',
                '070' => 'امانة منطقة القصيم',
                '071' => 'مدفوعات خدمات البنك السعودي الفرنسي',
                '072' => 'جامعة اليمامة',
                '073' => 'غرفة الرياض',
                '074' => 'دار اليوم للإعلام',
                '075' => 'وزارة النقل',
                '076' => 'الشركة العربية للوسائل',
                '077' => 'جمعيات تحفيظ القران الكريم',
                '078' => 'بنك الجزيرة',
                '079' => 'شركه الامثل',
                '080' => 'جامعة الملك سعود',
                '081' => 'قنوات المجد',
                '082' => 'مدفوعات البنك العربي',
                '083' => 'الشركة السعودية للحاسبات الألكترونية المحدودة',
                '084' => 'امانة منطقة عسير',
                '085' => 'إشعار',
                '086' => 'شركة الجبر للتمويل',
                '087' => 'امانة منطقة نجران',
                '088' => 'وزارة المالية',
                '089' => 'المؤسسة العامة للموانئ',
                '098' => 'الهيئة العامة للطيران المدني',
                '101' => 'وزارة الخارجية',
                '102' => 'أمانه الحدود الشمالية',
                '103' => 'امانة منطقة الجوف',
                '104' => 'أمانه منطقة الباحة',
                '105' => 'مدينة الملك عبدالعزيز للعلوم والتقنية',
                '106' => 'جامعة طيبة',
                '107' => 'سمارت كاش',
                '108' => 'وزارة الاستثمار',
                '109' => 'الهيئة العامه للغذاء والدواء',
                '110' => 'الراجحي تكافل',
                '112' => 'أمانة الاحساء',
                '113' => 'أمانة محافظة الطائف',
                '114' => 'الغرفة التجارية الصناعية بابها',
                '115' => 'المؤسسة العامة للتدريب التقني والمهني',
                '116' => 'وكالة الوزارة للثروة المعدنية',
                '117' => 'جامعة الملك فهد للبترول والمعادن',
                '118' => 'جامعة الملك فيصل',
                '120' => 'الخطوط الحديدية',
                '121' => 'دار الحياة للنشر والتوزيع',
                '122' => 'شركة توزيع',
                '123' => 'الهيئة السعودية للمهندسين',
                '124' => 'شركة النايفات للتمويل',
                '125' => 'مدفوعات بنك الإمارات دبي الوطني',
                '128' => 'وزارة الصحة',
                '129' => 'الهيئة السعودية للتخصصات الصحية',
                '130' => 'بوبا العربية',
                '131' => 'شركة راية للتمويل (مساهمة مقفلة)',
                '132' => 'جامعة أم القرى',
                '133' => 'جمعية القلب السعودية',
                '134' => 'الشركة المتحدة للإلكترونيات - إكسترا',
                '135' => 'الشركة السعودية لتبادل المعلومات الكترونيا',
                '136' => 'شركة الدرع العربي للتأمين التعاوني',
                '137' => 'الجامعة السعودية الإلكترونية',
                '138' => 'شركة المياه الوطنية',
                '139' => 'غرفة الشرقية',
                '140' => 'مدفوعات بنك البلاد',
                '141' => 'بوابة المشتريات الحكومية',
                '142' => 'برامج صندوق تنمية الموارد البشرية',
                '143' => 'صندوق التنمية الصناعية السعودي',
                '144' => 'خدمات أعمالي',
                '145' => 'شركة تطوير وتشغيل المدن الصناعية المحدودة',
                '146' => 'الهيئة الملكية في الجبيل',
                '147' => 'الشركة السعودية لتمويل المساكن',
                '148' => 'شركة الغاز والتصنيع الأهلية',
                '149' => '(سوا)شركة الاتصالات السعودية',
                '150' => 'الشركة السعودية للنقل الجماعي',
                '151' => 'فيرجن موبايل',
                '153' => 'إيجار',
                '154' => 'هيئة المدن الإقتصادية',
                '156' => 'تمكين للتقنيات',
                '157' => 'شركة الموارد للقوى البشرية',
                '159' => 'هيئة تقويم التعليم',
                '160' => 'وزارة العدل',
                '161' => 'وزارة الحج والعمرة',
                '162' => 'المؤسسة العامة للحبوب',
                '163' => 'مدفوعات البنك السعودي للإستثمار',
                '165' => 'شركة اوركس السعودية',
                '166' => 'اعمار، المدينة الاقتصادية',
                '167' => 'السعودية للإستقدام-سماسكو',
                '168' => 'معهد الإدارة العامة – مركز الاعمال',
                '169' => 'التنفيذ القضائي',
                '170' => 'الشركة العربية للخدمات الزراعية',
                '171' => 'وزارة الإسكان',
                '172' => 'شركة الجاسرية للتمويل',
                '175' => 'بداية لتمويل المنازل',
                '176' => 'مدينة الملك عبدالله للطاقة الذرية والمتجددة',
                '177' => 'وزارة الشؤون البلدية والقروية',
                '179' => 'شركة الأجير المنتدب للاستقدام',
                '180' => 'هيئة النقل العام',
                '181' => 'طيران أديل',
                '182' => 'سكني - الشركة الوطنية للاسكان',
                '183' => 'شركة سال للشحن المحدودة',
                '184' => 'الهيئة العامة للمعارض والمؤتمرات',
                '185' => 'الهيئة العامة للمعارض والمؤتمرات',
                '186' => 'شركة لين لخدمات الأعمال',
                '187' => 'ايجاره',
                '188' => 'شركة آجل للخدمات التمويلية',
                '189' => 'شركة بيان للمعلومات الائتمانية',
                '190' => 'المؤسسة العامة للتقاعد',
                '191' => 'الشركة السعودية للخطوط الحديدية',
                '192' => 'الهيئة العامة للأرصاد وحماية البيئة',
                '193' => 'نسما للطيران',
                '195' => 'شركة توكيلات للتمويل',
                '196' => 'المجموعة المتحدة للتأمين التعاوني (أسيج)',
                '197' => 'تأميني',
                '199' => 'شركة تكامل القابضة',
                '200' => 'مؤسسة الملك عبدالعزيز ورجاله للموهبة والإبداع - موهبة',
                '201' => 'الشركة الأهلية للاستقدام',
                '204' => 'الهيئة العامة للاعلام المرئي والمسموع',
                '206' => 'قطار الحرمين السريع',
                '207' => 'STC Pay',
                '208' => 'الهيئة السعودية للمقاولين',
                '209' => 'تطوير لتقنيات التعليم',
                '210' => 'شركة السحاب الوطنية',
                '211' => 'شركة التأجيير التمويلي',
                '212' => 'اللجنة الوطنية لمكافحة التبغ',
                '213' => 'شركة مهارة للموارد البشرية',
                '214' => 'شركة إساد لحلول وإدارة الموارد البشرية',
                '215' => 'جامعة جدة',
                '216' => 'دارة الملك عبدالعزيز',
                '218' => 'جامعة القصيم',
                '219' => 'هيئة العامة للإحصاء',
                '221' => 'المؤسسة العامة للري',
                '222' => 'حرس الحدود',
                '226' => 'أمارة منطقة نجران',
                '227' => 'الهيئة العامة للمساحة',
                '229' => 'المؤسسة العامة لتحلية المياه المالحة',
                '231' => 'لقوات البرية الملكية السعودية',
                '232' => 'خدمة المدفوعات لبنك أبوظبي الأول',
                '233' => 'الهيئة العامة لعقارات الدولة',
                '234' => 'الأمن العام',
                '235' => 'هيئة المساحة الجيولوجية',
                '236' => 'أمانة محافظة حفر الباطن',
                '237' => 'أمارة منطقة مكة المكرمة',
                '238' => 'الرئاسة العامة للبحوث العلمية والإفتاء',
                '239' => 'الهيئة العامة للحياة الفطرية',
                '240' => 'هيئة تطوير منطقة مكة المكرمة',
                '241' => 'وزارة التعليم',
                '242' => 'الهيئة السعودية للملكية الفكرية',
                '247' => 'كلية الملك فهد الأمنية',
                '248' => 'القوات الجوية الملكية السعودية',
                '249' => 'جامعة الملك خالد',
                '250' => 'مدينة الملك فيصل العسكرية',
                '251' => 'هيئة تنمية الصادرات السعودية',
                '252' => 'وزارة الحرس الوطني',
                '253' => 'هيئة تطوير منطقة المدينة المنورة',
                '254' => 'هيئة الهلال الأحمر السعودي',
                '255' => 'مساند',
                '256' => 'جامعة الحدود الشمالية',
                '257' => 'مستشفى الملك فيصل التخصصي ومركز الأبحاث',
                '258' => 'مركز كفاءة الطاقة السعودي',
                '259' => 'مركز الإقامة المميزة',
                '260' => 'رصيد جاك',
                '261' => 'تسهيل للتمويل',
                '265' => 'القوات البحرية الملكية السعودية',
                '266' => 'الهيئة العامة للمنافسة',
                '267' => 'وزارة الطاقة',
                '268' => 'الشركة الوطنية لخدمات التمويل',
                '269' => 'هيئة الرقابة النووية والإشعاعية',
                '270' => 'صندوق النفقة - وزارة العدل',
                '272' => 'الهيئة الملكية للجبيل وينبع',
                '275' => 'الهيئة العامة للترفيه',
                '276' => 'المركز السعودي لاعتماد المنشآت الصحية',
                '278' => 'المؤسسة العامة للصناعات العسكرية',
                '279' => 'المركز السعودي للأعمال الاقتصادية',
                '280' => 'النقل المدرسي',
                '282' => 'البيئة والمياه والزراعة',
                '283' => 'جامعة الأمير سلطان',
                '285' => 'الهيئة الوطنية للامن السيبراني',
                '287' => 'المركز الوطني للارصاد',
                '288' => 'المركز السعودي للاعتماد',
                '289' => 'جامعة الجوف',
                '291' => 'كلية الملك خالد العسكرية',
                '293' => 'الهيئة العامة للصناعات العسكرية',
                '295' => 'هيئة الإذاعة والتلفزيون',
                '297' => 'وزارة الرياضة',
                '298' => 'رئاسة هيئة الأركان العامة',
                '303' => 'كلية الملك عبدالله للدفاع الجوي',
                '306' => 'وكالة الانباء السعودية',
                '307' => 'أمارة منطقة الجوف',
                '309' => 'إمارة منطقة الباحة',
                '312' => 'الإفراغ العقاري الإلكتروني',
                '313' => 'وزارة الحرس الوطني الشؤون الصحية',
                '314' => 'لوحات المركبات المميزة',
                '343' => 'هيئة الصحة العامة',
                '639' => 'شركة خدمات النفط المحدودة',
                '901' => 'رسوم',
                '902' => 'مدفوعات إيفاء',
                '903' => 'إيداع',
                '904' => 'رئاسة هيئة الأركان العامة',
                '905' => 'جامعة جدة',
            ],
        ];
        foreach($lookupValues as $key => $values){
            foreach($values as $value => $replaceWith){
                \App\Models\SMS\ValueLookup::updateOrCreate([
                    'value' => $value,
                    'replaceWith' => $replaceWith,
                ], [
                    'key' => $key,
                ]);
            }
        }
    }
}