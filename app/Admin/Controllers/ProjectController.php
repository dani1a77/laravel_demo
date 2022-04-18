<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use App\Models\Project_category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use DB;

class ProjectController extends AdminController
{
    protected $title = 'Выпуски программ';



    public function __construct() {
        $path = str_replace("/public","",$_SERVER['DOCUMENT_ROOT']);
        include($path."/app/Admin/lang/ru.php");
        global $MESS;
    }

    protected function grid()
    {
        global $MESS;
        $grid = new Grid(new Project());
      $grid->model()->orderBy('id', 'desc');
        $News = new Project();
        $filter = [];
        $category_list = $News::category(["active"=>1]);
        foreach($category_list as $categ){
            $filter['category'][$categ->id] = $categ->name;
        }

        $grid->column('id',__($MESS['id']))->sortable();
        $grid->column('active', __($MESS['active']))->switch();
        $grid->column('sort', __($MESS['sort']))->editable();
        $grid->column('section_id', __($MESS['section_id']))->display(function ($section_id){
            $News = new Project();
            if(isset($section_id) && $section_id>0) {
                $category = $News::category(['id' => $section_id]);
                return $category[0]->name;
            } else return "";
        })->sortable()->filter($filter['category']);
        $grid->column('name', __($MESS['name']))->display(function (){
            return '<a href = "/admin/project_elements/'.$this->id.'/edit">'.$this->name.'</a>';
        });
        $grid->column('created_at', __($MESS['created_at']))->display(function ($date){
            $tmp = explode("T",$date);
            $tmp1 = explode(".0",$tmp[1]);
            $tmp2 = explode("-",$tmp[0]);
            return $tmp2[2].'.'.$tmp2[1].'.'.$tmp2[0].' '.$tmp1[0];
        });
        $grid->column('updated_at', __($MESS['updated_at']))->display(function ($date){
            $tmp = explode("T",$date);
            $tmp1 = explode(".0",$tmp[1]);
            $tmp2 = explode("-",$tmp[0]);
            return $tmp2[2].'.'.$tmp2[1].'.'.$tmp2[0].' '.$tmp1[0];
        });
        $grid->column('active_from', __($MESS['active_from']))->date('d-m-Y');
        $grid->column('active_to', __($MESS['active_to']))->date('d-m-Y');

        $grid->filter(function($filter){
            global $MESS;
            $filter->like('name', $MESS['name']);
            $filter->date('active_from', $MESS['active_from']);
            $filter->date('active_to', $MESS['active_to']);
        });




        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {

        global $MESS;
        $show = new Show(Project::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('section_id', __($MESS['section_id']));
        $show->field('created_at', __($MESS['created_at']))->format('DD-MM-YYYY');
        $show->field('updated_at', __($MESS['updated_at']))->format('DD-MM-YYYY');
        $show->datetime('active_from', __($MESS['active_from']))->default(date('Y-m-d H:i:s'))->format('YYYY-MM-DD HH:mm:ss');
        $show->datetime('active_to', __($MESS['active_to']))->format('YYYY-MM-DD HH:mm:ss');
        $show->field('title', __($MESS['title']));
        $show->field('description', __($MESS['description']));
        $show->field('keywords', __($MESS['keywords']));
        $show->image('detail_picture', __($MESS['detail_picture']))->default("");
        $show->image('preview_picture', __($MESS['preview_picture']))->default("");
        $show->field('name', __($MESS['name']));
        $show->field('user_id', __($MESS['user_id']));
        $show->textarea('preview_text', __($MESS['preview_text']))->default("");
        $show->textarea('detail_text', __($MESS['detail_text']))->default("");

        return $show;
    }



    protected function form()
    {
        global $MESS;
        $form = new Form(new Project());
        $form->tab('Данные', function ($form){
            global $MESS;
            $form->switch('active', $MESS['active']);
            $category = project_category::where('active', 1)->paginate(1000);
            $category_options = [0 => "без раздела"];
            foreach ($category as $val) {
                $category_options[$val->id] = $val->name;
            }
            $form->text('name', __($MESS['name']))->required();
            $form->number('sort', __($MESS['sort']))->default(500);
            $form->text('link', __($MESS['link']));
            $form->select('section_id', __($MESS['section_id']))->options($category_options)->required();
          $form->datetime('active_from', __($MESS['active_from']))->default(date('Y-m-d H:i:s'))->format('YYYY-MM-DD HH:mm:ss');
          $form->datetime('active_to', __($MESS['active_to']))->format('YYYY-MM-DD HH:mm:ss');
        })->tab('Детальная страница', function ($form) {
            global $MESS;
            $form->ckeditor('detail_text', __($MESS['detail_video']))->default("");
            $form->ckeditor('preview_text', __($MESS['preview_text']))->default("");
            $form->image('detail_picture', __($MESS['detail_picture']))->rules('nullable')->required();
        })->tab('SEO', function ($form) {
            global $MESS;
            $form->text('title', __($MESS['title']));
            $form->text('description', __($MESS['description']));
            $form->text('keywords', __($MESS['keywords']));
        });

      $form->saved(function (Form $form){
        self::save_form($form);
      });
        return $form;
    }

  function term2link($string)
  {
    $converter = array(
      'а' => 'a',   'б' => 'b',   'в' => 'v',
      'г' => 'g',   'д' => 'd',   'е' => 'e',
      'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
      'и' => 'i',   'й' => 'y',   'к' => 'k',
      'л' => 'l',   'м' => 'm',   'н' => 'n',
      'о' => 'o',   'п' => 'p',   'р' => 'r',
      'с' => 's',   'т' => 't',   'у' => 'u',
      'ф' => 'f',   'х' => 'h',   'ц' => 'c',
      'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
      'ь' => '',  'ы' => 'y',   'ъ' => '',
      'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

      'А' => 'A',   'Б' => 'B',   'В' => 'V',
      'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
      'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
      'И' => 'I',   'Й' => 'Y',   'К' => 'K',
      'Л' => 'L',   'М' => 'M',   'Н' => 'N',
      'О' => 'O',   'П' => 'P',   'Р' => 'R',
      'С' => 'S',   'Т' => 'T',   'У' => 'U',
      'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
      'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
      'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
      'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
      ' ' => '_',"«"=>"","»"=>"",'"'=>"","'"=>"",
      "("=>"",")"=>"","+"=>"","["=>"","]"=>"","{"=>"","}"=>"",
      "`"=>"","~"=>"","@"=>"","!"=>"","#"=>"","$"=>"","%"=>"","^"=>"","&"=>"",
      "*"=>"","="=>"","/"=>"","\/"=>"","."=>"",","=>"",";"=>"","&nbsp;"=>"_","?"=>"",":"=>""
    );
    $string = strip_tags($string);
    $string = htmlentities($string);
    $string = strtr($string, $converter);
    $string = $string.self::genn(5);
    $string = str_replace(" ","_",$string);
    $string = strtolower($string);
    return strtr($string, $converter);
  }

  function genn($n=1) {
    $p = "";
    rand(0,10000);
    for($i=0;$i<$n;$i++) $p.=rand(0,9);
    return $p;
  }

  protected function save_form($form)
  {
    if (!isset($form->link) || empty($form->link)) {
      if (isset($form->model()->id) && $form->model()->id > 0) {
        $link = self::term2link($form->name);

        if (!empty($link)) {
          DB::table('project')
            ->where('id', $form->model()->id)
            ->update(array(
              'link' => $link,
            ));
        }
      }
    }
  }
}
