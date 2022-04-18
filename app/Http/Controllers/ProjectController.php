<?php

  namespace App\Http\Controllers;

  use App\Models\Project;
  use App\Models\Blocks;
  use App\Models\Api;
  use Illuminate\Http\Request;
  use DB;

  class ProjectController extends Controller
  {

    public function index()
    {

      $News = new Project();
      $data = [];
      $data['title'] = "Проекты";
      $data['project_list'] = $News::category(["active" => 1]);
      $Blocks = new Blocks();
      $banners = $Blocks::get_banner(["radio_reklama1", "tv_index1", "category_block1", "category_block2"]);
      foreach ($banners as $banner) {
        if (isset($banner->code)) {
          $banner->detail_picture = "/upload/" . $banner->detail_picture;
          $data[$banner->code] = $banner;
        }
      }
      $data['project_list2'] = [];
      $count = 5;
      $i = 0;
      foreach ($data['project_list'] as $k => $project) {
        if (strpos($project->link, "http") !== false) {
          $data['project_list'][$k]->ext = 1;
        } else $data['project_list'][$k]->ext = 0;
        if ($i > $count) {
          $data['project_list2'][] = $project;
          unset($data['project_list'][$k]);
        }
        $i++;
      }

      $Api = new Api();
      $data['is_admin'] = $Api::is_admin();
      return view('project_list', $data);
    }

    public function project()
    {

      $Blocks = new Blocks();
      $data = [];
      $banners = $Blocks::get_banner(["tv_index1", "category_block1", "category_block2"]);
      foreach ($banners as $banner) {
        if (isset($banner->code)) {
          $banner->detail_picture = "/upload/" . $banner->detail_picture;
          $data[$banner->code] = $banner;
        }
      }

      $Api = new Api();
      $data['is_admin'] = $Api::is_admin();
      return view('project_list', $data);
    }

    public function live()
    {

      $data = [];
      $Api = new Api();
      $data['is_admin'] = $Api::is_admin();
      $Blocks = new Blocks();
      $banners = $Blocks::get_banner(["category_block1", "category_block2", "detail_banner1", "detail_banner2", "detail_banner3"]);
      foreach ($banners as $banner) {
        if (isset($banner->code)) {
          $banner->detail_picture = "/upload/" . $banner->detail_picture;
          $data[$banner->code] = $banner;
        }
      }
      return view('tv_live', $data);
    }

    public function programm()
    {

      $News = new Project();
      $Blocks = new Blocks();
      $data = [];
      $Api = new Api();
      $data['is_admin'] = $Api::is_admin();
      $key = 'tv_programm_' . md5("active=1&limit=10");
      $project = \Cache::get($key);
      if ($project === NULL) {
        $project = DB::table('project')->where('active', "1")->orderBy('sort', "asc")->limit(10)->get();
        \Cache::put($key, $project, 900);
      }

      if (isset($_GET['date']))
        $tv_programm = DB::table('tv_programm')->where('date', $_GET['date'])->where('active', "1")->first();
      else
        $tv_programm = DB::table('tv_programm')->where('date', date("Y-m-d"))->where('active', "1")->first();

      $tv_programm_times = DB::table('tv_programm')->where('date', '>=', date("Y-m-d"))->where('active', "1")->orderBy('date', "asc")->get();

      $data['times'] = [];
      if (isset($tv_programm_times)) {
        foreach ($tv_programm_times as $item) {
          $data['times'][] = ["date" => $item->date, "name" => $item->name, "id" => $item->id];
        }
      }
      $data['tv_programm'] = $tv_programm;

      if (isset($data['tv_programm']->date)) {
        $week = [
          "1" => "понедельник",
          "2" => "вторник",
          "3" => "среда",
          "4" => "четверг",
          "5" => "пятница",
          "6" => "суббота",
          "0" => "воскресенье",
        ];
        $data['date'] = explode("-", $data['tv_programm']->date);
        $time = strtotime($data['date'][2] . '-' . $data['date'][1] . '-' . $data['date'][0]);
        $date_w = date('w', $time);

        if (isset($week[$date_w])) {
          $data['week'] = $week[$date_w];
        }
      }

      if (isset($tv_programm->programm_json)) {
        if (isset($data['tv_programm']->programm_json))
          $data['tv_programm']->programm = json_decode($data['tv_programm']->programm_json);
        $current_time = date("H:i");
        $current_prog = [];
        $i = 0;
        $current_prog_i = 0;
        foreach ($data['tv_programm']->programm as $tv_prog) {
          if ($current_time > $tv_prog->Время) {
            $current_prog_i = $i;
            $current_prog = $tv_prog;
          }
          $i++;
        }

        foreach ($project as $k => $value) {
          if ($value->detail_picture) {
            $project[$k]->detail_picture = Api::resize_image($value->id, $value->detail_picture, "project", 330, 220);
          }

          $key = 'category_' . md5("id=" . $value->section_id);
          $category = \Cache::get($key);
          if ($category === NULL) {
            $category = $News::category(["id" => $value->section_id]);
            \Cache::put($key, $category, 900);
          }
          if ($k > 0 && isset($value->link) && isset($category[0]->link))
            $project[$k]->detail_link = "/tv/" . $category[0]->link . "/" . $value->link . "/";
        }

        $data['current_prog'] = $current_prog;
        $data['current_prog_i'] = $current_prog_i;
        $data['project'] = $project;
      }

      $banners = $Blocks::get_banner(["tv_index1", "category_block1", "category_block2"]);
      foreach ($banners as $banner) {
        if (isset($banner->code)) {
          $banner->detail_picture = "/upload/" . $banner->detail_picture;
          $data[$banner->code] = $banner;
        }
      }

      return view('tv_programm', $data);
    }

    public function category(Request $request)
    {
      $News = new Project();
      $data = [];
      if ($request->route('category_link')) {
        $category = $News::category(["link" => $request->route('category_link'), "active" => 1]);
        if (isset($category[0]->id)) {
          $Blocks = new Blocks();
          $banners = $Blocks::get_banner(["radio_reklama1", "radio_banner1", "radio_banner2", "category_block1", "category_block2"]);
          foreach ($banners as $banner) {
            if (isset($banner->code)) {
              $banner->detail_picture = "/upload/" . $banner->detail_picture;
              $data[$banner->code] = $banner;
            }
          }
          $category_news_list = $News::category_news_list($category);

          $data['current_page'] = $category_news_list['current_page'];
          $data['total'] = $category_news_list['total'];
          $data['next_page'] = $category_news_list['next_page'];
          $data['page_count'] = ceil($category_news_list['total'] / $category_news_list['perpage']);

          $data["title"] = $category[0]->name;
          $data['detail_picture'] = $category[0]->detail_picture;
          $data["text"] = html_entity_decode($category[0]->detail_text, ENT_QUOTES, 'utf-8');
          $data["programm_list"] = $category_news_list['news'];
          $i = 0;
          if (isset($data["programm_list"])) {
            foreach ($data["programm_list"] as $k => $v) {
              if ($i > 5) {
                $data["programm_list2"][] = $v;
                unset($data["programm_list"][$k]);
              }
              $i++;
            }
          }

          $Api = new Api();
          $data['is_admin'] = $Api::is_admin();
          if (isset($_GET['AJAX'])) {
            return view('project_category_ajax', $data);
          } else {
            return view('project_category', $data);
          }
        } else return view('404', []);
      } else {
        return view('404', []);
      }
    }

    public function detail_news(Request $request)
    {

      $News = new Project();
      if ($request->route('news_link') && $request->route('category_link')) {
        if ($request->route('category_link')) {
          $category = $News::category(["link" => $request->route('category_link'), "active" => 1]);
          $news_array = $News::detail_news($category, $request->route('news_link'));

          $Blocks = new Blocks();
          $data = [];
          $banners = $Blocks::get_banner(["category_block1", "category_block2", "detail_banner1", "detail_banner2", "detail_banner3"]);
          foreach ($banners as $banner) {
            if (isset($banner->code)) {
              $banner->detail_picture = "/upload/" . $banner->detail_picture;
              $data[$banner->code] = $banner;
            }
          }

          $category_news_list = $News::category_news_list($category, 5);
          $data["programm_list"] = $category_news_list['news'];

          $Api = new Api();
          $data['is_admin'] = $Api::is_admin();
          $data["title"] = $category[0]->name;
          $news_array["detail_text"] = html_entity_decode($news_array["detail_text"], ENT_QUOTES, 'utf-8');
          $data["news"] = $news_array;
          $data["category_link"] = "/tv/" . $request->route('category_link') . "/";

          return view('project_detail', $data);
        }
      }

      return false;
    }

  }
