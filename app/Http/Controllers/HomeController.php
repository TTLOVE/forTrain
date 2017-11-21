<?php

namespace App\Http\Controllers;
use \Curl\Curl;

class HomeController extends Controller
{
    CONST COOKIE_DIR = "/tmp/cookie/";

    /**
        * 验证码输入页面
        *
        * @return 
     */
    public function login()
    {
        $cookieVerify = self::COOKIE_DIR . date("Ymd") . ".verify.tmp";
        $cookieSuccess = self::COOKIE_DIR . date("Ymd") . ".tickets.tmp";

        // 获取cookie并保存
        $curl = new Curl();
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieVerify);
        $curl->setOpt(CURLOPT_HEADER, 0);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        date_default_timezone_set('PRC');//使用Cookie时，必须设置时区
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST,false);
        $curl->setOpt(CURLOPT_COOKIESESSION, TRUE);
        $curl->setOpt(CURLOPT_COOKIE, session_name().'='.session_id());
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->get("https://kyfw.12306.cn/otn/login/init");
        $curl->close();

        // 带上cookie抓取验证码，必须带上cookie，否则验证码不对应
        $imagePath = "storage/framework/cache/image.jpg";
        $fp = fopen($imagePath,'wb');//把请求的图片二进制文件写入image.jpg中
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FILE,$fp);
        $curl->setOpt(CURLOPT_HEADER,0);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION,1);
        date_default_timezone_set('PRC');//设置时区                               
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            
        //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_TIMEOUT,60);
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieVerify); //读取cookie
        $curl->get("https://kyfw.12306.cn/passport/captcha/captcha-image?login_site=E&module=login&rand=sjrand");
        $curl->close();
        fclose($fp);
        return view('login')->with('imgUrl', $imagePath);
    }


    /**
        * 校验验证码并登陆页面
        *
        * @return 
     */
    public function checkCha()
    {
        $cookieVerify = self::COOKIE_DIR . date("Ymd") . ".verify.tmp";
        $cookieSuccess = self::COOKIE_DIR . date("Ymd") . ".tickets.tmp";

        $verify = rtrim($_POST["randCode"],',');
        $data = [
            'login_site' =>'E',
            'answer' => $verify,
            'rand' => 'sjrand'
        ];
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieVerify);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_POST, 1);
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_POSTFIELDS, $data);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $curl->post("https://kyfw.12306.cn/passport/captcha/captcha-check", $data);
        echo "\n\n";
        var_export($curl->response);
        echo "\n\n";
        exit;






        // 验证验证码
        $url = "https://kyfw.12306.cn/passport/captcha/captcha-check"; 
        //$data = 'rand=sjrand&answer='.$verify;
        $data = '';
        // 返回结果存放在变量中，不输出 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieVerify);
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieSuccess);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $result= curl_exec($ch);
        echo "\n\n";
        var_export($result);
        echo "\n\n";
        exit;

        // 用户名\密码 
        $urls = "https://kyfw.12306.cn/otn/login/loginAysnSuggest"; 
        $datas = 'loginUserDTO.user_name=348977791@qq.com&userDTO.password=qq6661726&randCode='.$verify;

        // 返回结果存放在变量中，不输出 
        $chs = curl_init(); 
        curl_setopt($chs, CURLOPT_URL, $urls);
        curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($chs, CURLOPT_COOKIEFILE, $cookieVerify);
        curl_setopt($chs, CURLOPT_HEADER, 0); 
        curl_setopt($chs, CURLOPT_CONNECTTIMEOUT, 120); 
        curl_setopt($chs, CURLOPT_POST, 1);
        curl_setopt($chs, CURLOPT_COOKIEJAR, $cookieSuccess);
        curl_setopt($chs, CURLOPT_POSTFIELDS, $datas);
        curl_setopt($chs, CURLOPT_SSL_VERIFYPEER, FALSE);           //终止从服务端进行验证
        curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($chs, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($chs, CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $results= curl_exec($chs);

        if(!curl_errno($chs)){
            curl_setopt($chs, CURLOPT_URL, "https://kyfw.12306.cn/otn/index/initMy12306");
            curl_setopt($chs, CURLOPT_HTTPHEADER, array("Content-type:text/html"));
            curl_setopt($chs, CURLOPT_SSL_VERIFYPEER, FALSE);           
            //终止从服务端进行验证
            curl_setopt($chs, CURLOPT_SSL_VERIFYHOST, FALSE);
            $output = curl_exec($chs);
            $output=str_replace("<head>",
                "<head><base href='https://kyfw.12306.cn'> ", $output);
            echo $output;

        }else{
            echo 'curl error:'.curl_error($chs);
        }
        curl_close($ch);
        curl_close($chs);
    }

    /**
     * 生成随机字符串
     *
     * @param $length 
     *
     * @return string
     */
    public function createRoundStr($length = 8)
    {
        $strArray = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $roundStr = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $roundStr .= $strArray[mt_rand(0, 61)];
        }
        return $roundStr;
    }
}
