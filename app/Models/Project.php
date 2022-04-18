<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Model;
  use Encore\Admin\Traits\AdminBuilder;
  use Encore\Admin\Traits\ModelTree;
  use DB;

  class Project extends Model
  {
    //
    protected $table = "project";

    public static function category($where)
    {
      $category = DB::table('project_category')->where($where)->orderBy('sort', "asc")->get();
      return $category;
    }

    /* Выборка проектов для раздела */
    public static function category_news_list($category = [], $limit = 9)
    {
      if (isset($category[0]->id)) {
        $news_list = [];
        $category_news_list = DB::table('project')->where('section_id', $category[0]->id)->where('active', "1")->Where(function ($query){
          $query->orWhere(function ($query){
            $query->where('active_from', '<=', date("Y-m-d H:i:s"))
              ->orWhere('active_from', '=', NULL);
          })->Where(function ($query){
            $query->where('active_to', '>=', date("Y-m-d H:i:s"))
              ->orWhere('active_to', '=', NULL);
          });
        })->orderBy('id', "desc")->paginate($limit);;
        foreach ($category_news_list as $news) {
          $detail_link = "/tv/" . $category[0]->link . "/" . $news->link . "/";
          $news_list[] = [
            "id" => $news->id,
            "section_id" => $news->section_id,
            "category_name" => $category[0]->name,
            "created_at" => $news->created_at,
            "name" => $news->name,
            "preview_text"=>html_entity_decode($news->preview_text),
            "detail_picture" => $news->detail_picture,
            "detail_text" => $news->detail_text,
            "detail_link" => $detail_link,
          ];
        }
      } else {
        $news_list = [];
        $category_list = [];
        $category_news_list = DB::table('project')->where('active', "1")->get();
        $categorys = DB::table('project_category')->where('active', "1")->get();
        foreach ($categorys as $news_c) {
          $category_list[$news_c->id] = ["link" => $news_c->link, "name" => $news_c->name];
        }
        foreach ($category_news_list as $news) {
          $detail_link = "/tv/" . $category_list[$news->section_id]['link'] . "/" . $news->link . "/";
          $news_list[] = [
            "id" => $news->id,
            "section_id" => $news->section_id,
            "category_name" => $category_list[$news->section_id]['name'],
            "created_at" => $news->created_at,
            "name" => $news->name,
            "preview_picture" => $news->preview_picture,
            "detail_picture" => $news->detail_picture,
            "preview_text"=>html_entity_decode($news->preview_text, ENT_QUOTES, 'utf-8'),
            "detail_text" => $news->detail_text,
            "detail_link" => $detail_link,
          ];
        }
      }

      $page = 1;
      if ($category_news_list->currentPage()) {
        $count_page = ceil($category_news_list->total() / $category_news_list->perPage());
        $page = intval($category_news_list->currentPage());
        $page = $page + 1;
        if ($page > $count_page)
          $page = $page - 1;
      }

      return [
        "next_page" => $page,
        "current_page" => $category_news_list->currentPage(),
        "perpage" => $category_news_list->perPage(),
        "total" => $category_news_list->total(),
        "links" => $category_news_list->links(),
        "news" => $news_list
      ];
    }

    /* Обрабатываем косяки в контенте и закрываем теги - не закрытые */
    public static function close_tags($content)
    {
      $position = 0;
      $open_tags = array();
      //теги для игнорирования
      $ignored_tags = array('br', 'hr', 'img');

      while (($position = strpos($content, '<', $position)) !== FALSE)
      {
        //забираем все теги из контента
        if (preg_match("|^<(/?)([a-z\d]+)\b[^>]*>|i", substr($content, $position), $match))
        {
          $tag = strtolower($match[2]);
          //игнорируем все одиночные теги
          if (in_array($tag, $ignored_tags) == FALSE)
          {
            //тег открыт
            if (isset($match[1]) AND $match[1] == '')
            {
              if (isset($open_tags[$tag]))
                $open_tags[$tag]++;
              else
                $open_tags[$tag] = 1;
            }
            //тег закрыт
            if (isset($match[1]) AND $match[1] == '/')
            {
              if (isset($open_tags[$tag]))
                $open_tags[$tag]--;
            }
          }
          $position += strlen($match[0]);
        }
        else
          $position++;
      }
      //закрываем все теги
      foreach ($open_tags as $tag => $count_not_closed)
      {
        $content .= str_repeat("</{$tag}>", $count_not_closed);
      }

      return $content;
    }

    /* Детальная страница*/
    public static function detail_news($category, $link)
    {
      if ($link) {
        $news_array = [];
        $news = DB::table('project')->where('link', $link)->where('active', "1")->first();
        if (isset($news->id)) {
          $news->detail_text = html_entity_decode($news->detail_text);
          $news->detail_text = trim($news->detail_text,'"');
          $news->detail_text = htmlspecialchars($news->detail_text);
          $news->detail_text = html_entity_decode($news->detail_text);
          $news->detail_text = htmlspecialchars_decode($news->detail_text);
          $news->detail_text = trim($news->detail_text,'"');
          $news->detail_text = self::close_tags($news->detail_text);
          $detail_link = "/tv/" . $category[0]->link . "/" . $news->link . "/";
          $news_array = [
            "id" => $news->id,
            "section_id" => $news->section_id,
            "category_name" => $category[0]->name,
            "created_at" => $news->created_at,
            "preview_text"=>html_entity_decode($news->preview_text, ENT_QUOTES, 'utf-8'),
            "name" => $news->name,
            "detail_picture" => "/upload/" . $news->detail_picture,
            "detail_text" => $news->detail_text,
            "detail_link" => $detail_link,
          ];
        }
        return $news_array;
      }
    }

    /* Выборка свойств проекта*/
    public static function get_property()
    {
      $property = DB::table('property')->where('element_type', "news")->where('active', "1")->get();
      return $property;
    }

    public static function get_list($where)
    {
      $news = DB::table('project')->where($where)->get();
      if (isset($news[0]->id))
        return $news;
      return false;
    }

    /* Добавляем проект */
    public static function insert($fields)
    {
      DB::table('project')->insert($fields);
    }
  }
