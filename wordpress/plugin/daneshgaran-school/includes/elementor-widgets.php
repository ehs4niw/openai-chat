<?php
if(!defined('ABSPATH')||!class_exists('\\Elementor\\Widget_Base'))exit;
class DGS_Elementor_Live_Widget extends \Elementor\Widget_Base{
 public function get_name(){return 'dgs_live';}
 public function get_style_depends(){return array('dgs-showcase','dgs-elementor-design');}
 public function get_script_depends(){return array('dgs-showcase');}
 protected function is_dynamic_content(): bool{return true;}
 public function get_title(){return 'اطلاعات زنده دانشگران';}
 public function get_icon(){return 'eicon-post-list';}
 public function get_categories(){return array('general');}
 public function get_keywords(){return array('دانشگران','اساتید','اخبار','کادر','برگزیده');}
 protected function register_controls(){
  $this->start_controls_section('content',array('label'=>'اطلاعات سامانه'));
  $this->add_control('kind',array('label'=>'نوع اطلاعات','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'teachers','options'=>array('teachers'=>'اساتید','staff'=>'کادر مدرسه','director'=>'مدیر مدرسه','featured'=>'دانش‌آموز برگزیده','news'=>'اخبار','contact'=>'اطلاعات تماس عمومی')));
  $this->add_control('count',array('label'=>'تعداد نمایش','type'=>\Elementor\Controls_Manager::NUMBER,'default'=>6,'min'=>1,'max'=>30,'condition'=>array('kind'=>array('teachers','staff','news'))));
  $this->add_control('category',array('label'=>'گروه خبر','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'school','options'=>array('school'=>'اخبار مدرسه','ministry'=>'آموزش و پرورش'),'condition'=>array('kind'=>'news')));
  $this->add_control('staff_group',array('label'=>'گروه کادر','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'all','options'=>array('all'=>'همه کادر','deputies'=>'معاونت'),'condition'=>array('kind'=>'staff')));
  $staff=array('0'=>'انتخاب از تنظیمات مدرسه');foreach(dgs_showcase_staff() as $post)$staff[(string)$post->ID]=$post->post_title;
  $this->add_control('director_id',array('label'=>'مدیر نمایش‌داده‌شده','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'0','options'=>$staff,'condition'=>array('kind'=>'director')));
  $this->add_control('director_text',array('label'=>'توضیح اختصاصی (خالی باشد از رزومه خوانده می‌شود)','type'=>\Elementor\Controls_Manager::TEXTAREA,'rows'=>5,'condition'=>array('kind'=>'director')));
  $this->add_control('carousel',array('label'=>'اسلایدر اساتید / کادر','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'','condition'=>array('kind'=>array('teachers','staff'))));
  $this->add_responsive_control('columns',array('label'=>'تعداد ستون‌ها','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'3','tablet_default'=>'2','mobile_default'=>'1','options'=>array('1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶'),'selectors'=>array('{{WRAPPER}} .dse-live-grid'=>'grid-template-columns:repeat({{VALUE}},minmax(0,1fr));')));
  $this->end_controls_section();
  $this->start_controls_section('style',array('label'=>'ظاهر کارت‌ها','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
  foreach(array('text_color'=>'رنگ متن','accent_color'=>'رنگ تأکید','card_bg'=>'پس‌زمینه کارت','border_color'=>'رنگ حاشیه') as $key=>$label){$selectors=array('text_color'=>array('{{WRAPPER}} .dse-live, {{WRAPPER}} .dse-live h3, {{WRAPPER}} .dse-live a'=>'color:{{VALUE}};'),'accent_color'=>array('{{WRAPPER}} .dse-live svg'=>'color:{{VALUE}};'),'card_bg'=>array('{{WRAPPER}} .dsh-person, {{WRAPPER}} .dsh-news-row'=>'background:{{VALUE}};'),'border_color'=>array('{{WRAPPER}} .dsh-person, {{WRAPPER}} .dsh-news-row'=>'border:1px solid {{VALUE}};'));$this->add_control($key,array('label'=>$label,'type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>$selectors[$key]));}
  $this->add_responsive_control('title_size',array('label'=>'اندازه عنوان کارت‌ها','type'=>\Elementor\Controls_Manager::SLIDER,'range'=>array('px'=>array('min'=>10,'max'=>50)),'selectors'=>array('{{WRAPPER}} .dse-live h3'=>'font-size:{{SIZE}}{{UNIT}}!important;')));
  $this->add_responsive_control('body_size',array('label'=>'اندازه توضیحات','type'=>\Elementor\Controls_Manager::SLIDER,'range'=>array('px'=>array('min'=>10,'max'=>30)),'selectors'=>array('{{WRAPPER}} .dse-live p, {{WRAPPER}} .dse-live .dsh-person span'=>'font-size:{{SIZE}}{{UNIT}};')));
  $this->add_responsive_control('photo_size',array('label'=>'اندازه تصویر اساتید','type'=>\Elementor\Controls_Manager::SLIDER,'range'=>array('px'=>array('min'=>40,'max'=>200)),'selectors'=>array('{{WRAPPER}} .dsh-person-photo'=>'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}};')));
  $this->end_controls_section();
 }
 protected function render(){echo dgs_elementor_live_render($this->get_settings_for_display());}
}
class DGS_Elementor_Picture_Widget extends \Elementor\Widget_Base{
 public function get_name(){return 'dgs_picture';}
 public function get_style_depends(){return array('dgs-showcase','dgs-elementor-design');}
 public function get_title(){return 'تصویر مدرسه دانشگران';}
 public function get_icon(){return 'eicon-image';}
 public function get_categories(){return array('general');}
 protected function register_controls(){
  $this->start_controls_section('content',array('label'=>'تصویر'));
  $this->add_control('image',array('label'=>'انتخاب عکس واقعی از رسانه','type'=>\Elementor\Controls_Manager::MEDIA,'default'=>array('url'=>'')));
  $this->add_control('reference',array('label'=>'تصویر طرح در نبود عکس انتخابی','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'hero','options'=>array('hero'=>'نمای مدرسه','gallery0'=>'کلاس درس','gallery1'=>'آزمایشگاه','gallery2'=>'کتابخانه','gallery3'=>'حیاط')));
  $this->add_control('alt',array('label'=>'متن جایگزین عکس (برای دسترس‌پذیری و سئو)','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'تصویر مدرسه دانشگران'));
  $this->add_control('caption',array('label'=>'عنوان عکس (اختیاری)','type'=>\Elementor\Controls_Manager::TEXT));
  $this->add_control('priority',array('label'=>'تصویر اصلی بالای صفحه','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>''));
  $this->end_controls_section();
  $this->start_controls_section('style',array('label'=>'ظاهر تصویر','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
  $this->add_responsive_control('height',array('label'=>'ارتفاع','type'=>\Elementor\Controls_Manager::SLIDER,'default'=>array('size'=>240,'unit'=>'px'),'range'=>array('px'=>array('min'=>80,'max'=>800)),'selectors'=>array('{{WRAPPER}} .dse-picture-frame'=>'height:{{SIZE}}{{UNIT}};')));
  $this->add_control('radius',array('label'=>'گردی گوشه‌ها','type'=>\Elementor\Controls_Manager::SLIDER,'default'=>array('size'=>14,'unit'=>'px'),'range'=>array('px'=>array('min'=>0,'max'=>100)),'selectors'=>array('{{WRAPPER}} .dse-picture-frame'=>'border-radius:{{SIZE}}{{UNIT}};')));
  $this->add_control('fit',array('label'=>'نمایش عکس','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'cover','options'=>array('cover'=>'پوشاندن کادر','contain'=>'نمایش کامل'),'selectors'=>array('{{WRAPPER}} .dse-picture-frame img'=>'object-fit:{{VALUE}};')));
  $this->end_controls_section();
 }
 protected function render(){echo dgs_elementor_picture_render($this->get_settings_for_display());}
}
class DGS_Elementor_Icon_Widget extends \Elementor\Widget_Base{
 public function get_name(){return 'dgs_icon';}
 public function get_style_depends(){return array('dgs-elementor-design');}
 public function get_title(){return 'آیکن دانشگران';}
 public function get_icon(){return 'eicon-star';}
 public function get_categories(){return array('general');}
 protected function register_controls(){
  $this->start_controls_section('icon',array('label'=>'آیکن'));
  $this->add_control('icon_name',array('label'=>'نماد','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'study','options'=>array('study'=>'کتاب','education'=>'آموزش','users'=>'افراد','teacher'=>'معلم','building'=>'مدرسه','target'=>'رسالت','image'=>'تصویر','settings'=>'امکانات','timetable'=>'تقویم','trophy'=>'برگزیده','phone'=>'تلفن','home'=>'خانه')));
  $this->add_control('color',array('label'=>'رنگ','type'=>\Elementor\Controls_Manager::COLOR,'default'=>'#7b28a4','selectors'=>array('{{WRAPPER}} .dse-icon'=>'color:{{VALUE}};')));
  $this->add_control('size',array('label'=>'اندازه','type'=>\Elementor\Controls_Manager::SLIDER,'default'=>array('size'=>30,'unit'=>'px'),'range'=>array('px'=>array('min'=>15,'max'=>150)),'selectors'=>array('{{WRAPPER}} .dse-icon svg'=>'width:{{SIZE}}{{UNIT}}!important;height:{{SIZE}}{{UNIT}}!important;')));
  $this->add_control('align',array('label'=>'چیدمان','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'center','options'=>array('right'=>'راست','center'=>'وسط','left'=>'چپ'),'selectors'=>array('{{WRAPPER}} .dse-icon'=>'text-align:{{VALUE}};')));
  $this->end_controls_section();
 }
 protected function render(){$s=$this->get_settings_for_display();$allowed=array('study','education','users','teacher','building','target','image','settings','timetable','trophy','phone','home');$icon=in_array($s['icon_name']??'',$allowed,true)?$s['icon_name']:'study';echo '<div class="dse-icon">'.dgs_showcase_icon($icon).'</div>';}
}
