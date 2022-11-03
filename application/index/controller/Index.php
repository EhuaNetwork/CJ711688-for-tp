<?php

namespace app\Index\controller;


use Ehua\Caiji\Selenum;
use Ehua\Tool\Tool;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use MongoDB\Driver\Session;


use think\Db;
class Index
{
    
    function index()
    {
        set_time_limit(0);
        $info = $this->config['auth'][$this->i];

        try {
            Db::name('info')->where('id','neq',0)->delete();
            Db::name('nav')->where('id','neq',0)->delete();
            Db::name('shop_list')->where('id','neq',0)->delete();
            Db::name('shop_type')->where('id','neq',0)->delete();
            Tool::file_deldir(ROOT_PATH .'public\files');
        } catch (\ErrorException $r) {
        }
        //登录
        $this->login();
        //抓取公司信息
        $data1 = $this->info();
        $data1['account'] = $info['name'];
  
      $r= Db::name('info')->insert($data1);

        //抓取首页nav
        $data1 = $this->nav_type();
        foreach ($data1 as $d) {
            $d['account'] = $info['name'];
            $d['img_local'] = $info['url'] . $d['img_local'];
            Db::name('nav')->insert($d);
        }


        //抓取商品分类
        $data1 = $this->shop_type();
        foreach ($data1 as $d) {
            $d['account'] = $info['name'];
            unset($d['url']);
            $d['img_local'] = $info['url'] . $d['img_local'];
            Db::name('shop_type')->insert($d);
        }


        $max_page = '100';
        for ($i = 1; $i < $max_page; $i++) {
            //抓取商品列表
            $data1 = $this->shop_list($i);
            foreach ($data1 as $d) {
                $d['account'] = $info['name'];
                $d['img_local'] = $info['url'] . $d['img_local'];

                $a = $d['img_local'];
                $d['lunbo'] = "[\"$a\"]";
                Db::name('shop_list')->insert($d);
            }
        }
 

        echo 'ok';

        $this->driver->close();
    }

    public function __construct()
    {
        $this->i = 0;
        $this->PATH = 'files/';
        $this->DOMAIN = 'https://weadmin.711688.net.cn';
        $this->config = get_config();
        $this->driver = Selenum::init(false, ROOT_PATH . '\public\lib\Chrome-bin\chrome.exe');
//        $this->driver = Selenum::Session_init('895ea7b9b5dc4759faad0fe042e7632f');

        Tool::create_dir( $this->PATH);

        var_dump($this->driver->getSessionID());

    }

    /**
     * 登录
     * @return void
     */
    function login()
    {
        $driver = $this->driver;
        $info = $this->config['auth'][$this->i];

        $driver->get($this->config['base_url']);
        Selenum::js($driver, file_get_contents('http://libs.baidu.com/jquery/2.1.4/jquery.min.js'));
        $driver->findElement(WebDriverBy::xpath('//*[@id="UserName"]'))->sendKeys($info['name']);
        $driver->findElement(WebDriverBy::xpath('//*[@id="Password"]'))->sendKeys($info['pass']);
        Selenum::click($driver, WebDriverBy::xpath('//*[@id="sbt"]'));
        sleep(3);
    }

    /**
     * 抓取nav
     * @return void
     */
    function nav_type()
    {
        $driver = $this->driver;
        $info = $this->config['auth'][$this->i];
        $PATH = $this->PATH;
        $data1 = [];

        //抓取分类导航
        $driver->get("http://weadmin.711688.net.cn/storeadmin/store/widgetconfiglist?code=index_nav_icon");
        $rr = $driver->findElements(WebDriverBy::xpath('/html/body/div[2]/table/tbody/tr'));
        for ($i = 1; $i < count($rr) + 1; $i++) {
            $data1[$i]['title'] = $driver->findElement(WebDriverBy::xpath("/html/body/div[2]/table/tbody/tr[$i]/td[2]"))->getText();
            $data1[$i]['img'] = $driver->findElement(WebDriverBy::xpath("/html/body/div[2]/table/tbody/tr[$i]/td[3]/img"))->getAttribute('src');
            $img_local_name = basename($data1[$i]['img']);
            Tool::dir_create($PATH . $info['name'] . '/nav_type/');
            $data1[$i]['img_local'] = $PATH . $info['name'] . '/nav_type/' . $img_local_name;
            Tool::downlad_file($data1[$i]['img'], $data1[$i]['img_local']);
        }
        return $data1;
    }

    /**
     * 抓取商品分类
     * @return void
     */
    function shop_type()
    {
        $driver = $this->driver;
        $info = $this->config['auth'][$this->i];
        $PATH = $this->PATH;

        $driver->get("http://weadmin.711688.net.cn//storeadmin/store/storeclasslist");
        sleep(3);
        $rr = $driver->findElements(WebDriverBy::xpath('//*[@id="categoryTree"]/div/table/tbody/tr'));
        $data1 = [];
        for ($i = 1; $i < count($rr) + 1; $i++) {

            $data1[$i]['title'] = $driver->findElement(WebDriverBy::xpath('//*[@id="categoryTree"]/div/table/tbody/tr[' . $i . ']/th'))->getText();
            $data1[$i]['url'] = $driver->findElement(WebDriverBy::xpath('//*[@id="categoryTree"]/div/table/tbody/tr[' . $i . ']/td[2]/a[1]'))->getAttribute('href');
        }
        foreach ($data1 as $ii => $d) {

            $driver->get($data1[$ii]['url']);
            try {
                $data1[$ii]['img'] = $driver->findElement(WebDriverBy::xpath('/html/body/div[1]/form/table/tbody/tr[3]/td[2]/div/div[1]/img'))->getAttribute('src');
                $img_local_name = basename($data1[$ii]['img']);
                Tool::dir_create($PATH . $info['name'] . '/shop_type/');
                $data1[$ii]['img_local'] = $PATH . $info['name'] . '/shop_type/' . $img_local_name;
                Tool::downlad_file($data1[$ii]['img'], $data1[$ii]['img_local']);
            } catch (WebDriverException $e) {
                $data1[$ii]['img'] = null;
                $data1[$ii]['img_local'] = null;
            }
        }

        return $data1;
    }

    /**
     * 抓取商品列表
     * @return void
     * @throws \Facebook\WebDriver\Exception\UnexpectedTagNameException
     */
    public function shop_list($max_page=1)
    {
        $driver = $this->driver;
        $info = $this->config['auth'][$this->i];
        $PATH = $this->PATH;


        //抓取商品列表
        $driver->get("https://weadmin.711688.net.cn/storeadmin/product/onsaleproductlist?pageNumber=$max_page");

        try {
//            $driver->findElement(WebDriverBy::xpath('/html/body/form/div[3]/div[2]/input[1]'))->sendKeys(2000);
//            Selenum::click($driver, WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr/td[9]/input'));
//            sleep(3);
        } catch (WebDriverException $exception) {

        }
        $rr = $driver->findElements(WebDriverBy::xpath('/html/body/form/div[2]/table/tbody/tr'));
        $data1=[];
        for ($i = 1; $i < count($rr) + 1; $i++) {
            $data1[$i]['title'] = $driver->findElement(WebDriverBy::xpath("/html/body/form/div[2]/table/tbody/tr[$i]/td[4]"))->getText();
            $data1[$i]['img'] = $driver->findElement(WebDriverBy::xpath("/html/body/form/div[2]/table/tbody/tr[$i]/td[3]/img"))->getAttribute('src');

            $img_local_name = basename($data1[$i]['img']);
            Tool::dir_create($PATH . $info['name'] . '/shop_list/');
            $data1[$i]['img_local'] = $PATH . $info['name'] . '/shop_list/' . $img_local_name;
            Tool::downlad_file($data1[$i]['img'], $data1[$i]['img_local']);

            $data1[$i]['store'] = $driver->findElement(WebDriverBy::xpath("/html/body/form/div[2]/table/tbody/tr[$i]/td[10]/input"))->getAttribute('value');
            $data1[$i]['info_url'] = $driver->findElement(WebDriverBy::xpath("/html/body/form/div[2]/table/tbody/tr[$i]/td[12]/a[5]"))->getAttribute('href');
        }


        //商品详情抓取
        foreach ($data1 as $ii => $d) {
            $driver->get($d['info_url']);
            $data1[$ii]['money1'] = $driver->findElement(WebDriverBy::xpath('//*[@id="ShopPrice"]'))->getAttribute('value');
            $data1[$ii]['money2'] = $driver->findElement(WebDriverBy::xpath('//*[@id="MarketPrice"]'))->getAttribute('value');
            $data1[$ii]['money3'] = $driver->findElement(WebDriverBy::xpath('//*[@id="CostPrice"]'))->getAttribute('value');

            $selectAcao = new WebDriverSelect($driver->findElement(WebDriverBy::xpath('//*[@id="StoreCid"]')));
            $op_list = $selectAcao->getOptions();

            foreach ($op_list as $op_lis) {
                if ($op_lis->isSelected()) {
                    $data1[$ii]['type'] = $op_lis->getText();
                };
            }
            Selenum::click($driver, WebDriverBy::xpath('/html/body/ul/li[5]'));
            $info_html = $driver->findElement(WebDriverBy::xpath('//*[@id="bmaEditor"]'))->getAttribute('innerHTML');
            Tool::dir_create($PATH . $info['name'] . '/shop_list/');
            $info_html = $this->html_put_img('', $PATH . $info['name'] . '/shop_list/', $info_html);
            $data1[$ii]['body'] = $info_html;
        }
        return $data1;
    }

    /**
     * 抓取公司信息
     * @return void
     */
    function info()
    {
        $driver = $this->driver;
        $info = $this->config['auth'][$this->i];
        $PATH = $this->PATH;


        //公司简介部分
        $driver->get("https://weadmin.711688.net.cn/storeadmin/store/editstore");


        $data1['shop_name'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[3]/td[2]/input'))->getAttribute('value');
        $data1['shop_phone'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[8]/td[2]/input'))->getAttribute('value');
        $data1['shop_tel'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[9]/td[2]/input'))->getAttribute('value');
        $data1['shop_wx'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[10]/td[2]/input'))->getAttribute('value');
        $data1['shop_qq'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[11]/td[2]/input'))->getAttribute('value');

        $data1['address_sheng'] = Selenum::getSelect($driver, WebDriverBy::xpath('//*[@id="provinceSelect"]'));
        $data1['address_shi'] = Selenum::getSelect($driver, WebDriverBy::xpath('//*[@id="citySelect"]'));
        $data1['address_qu'] = Selenum::getSelect($driver, WebDriverBy::xpath('//*[@id="countySelect"]'));


        $data1['address_info'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[17]/td[2]/textarea'))->getAttribute('value');


        $data1['shop_info'] = $this->html_put_img('', $PATH . $info['name'] . '/info/', $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[20]/td[2]/div[2]/div[2]/div'))->getAttribute('innerHTML'));


        $data1['coord'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[1]/table/tbody/tr[18]/td[2]/input'))->getAttribute('value');
        $data1['shop_mail'] = $driver->findElement(WebDriverBy::xpath('/html/body/form/div[2]/table/tbody/tr[1]/td[2]/input'))->getAttribute('value');


        return $data1;
    }

    public function body()
    {

    }

    /**
     * html图片转本地
     * @param $domain           域名补全
     * @param $topath           相对路径   不要用全路径
     * @param $html             html
     * @return array|bool|\phpQuery|\QueryTemplatesParse|\QueryTemplatesSource|\QueryTemplatesSourceQuery|string|string[]|\unknown_type
     */
    public function html_put_img($domain, $topath, $html)
    {
        $info = $this->config['auth'][$this->i];

        Tool::dir_create($topath);

        $res = Tool::str_To_Utf8($html);
        $res = str_replace('gb2312', 'utf-8', $res);

        $res = \phpQuery::newDocument($res);
        \phpQuery::selectDocument($res);
        $imgs = pq('')->find('img');
        $body = pq('')->html();
        //去除所有img
        for ($i = 0; $i < $imgs->count(); $i++) {
            $temp_img = $imgs->eq($i)->attr('src');
            if (substr($temp_img, 0, 1) == '\\' || substr($temp_img, 0, 1) == '/') {
                $img = $domain . ($temp_img);
            } else {
                $img = $temp_img;
            }

            $img = Tool::str_to_url($img);

            $rand = basename($img);
            file_put_contents($topath . $rand, file_get_contents(($img), false, stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ])));
            $body = str_replace($temp_img, $info['url'] . $topath . $rand, $body);
        }
        return $body;
    }
}
