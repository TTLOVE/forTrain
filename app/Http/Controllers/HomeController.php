<?php

namespace App\Http\Controllers;
use \Curl\Curl;

class HomeController extends Controller
{
    CONST COOKIE_VERIFY = "/tmp/verify.tmp";
    CONST COOKIE_SUCCESS = "/tmp/verify.tmp";
    //CONST COOKIE_SUCCESS = "/tmp/tickets.tmp";

    /**
        * 验证码输入页面
        *
        * @return 
     */
    public function index()
    {
        $imagePath = $this->getCaptchaImage();
        return view('login')->with('imgUrl', $imagePath);
    }

    /**
        * 获取验证码
        *
        * @return string
     */
    private function getCaptchaImage()
    {
        // 带上cookie抓取验证码，必须带上cookie，否则验证码不对应
        $imagePath = "storage/framework/cache/image.jpg";
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;
        $fp = fopen($imagePath,'wb');//把请求的图片二进制文件写入image.jpg中
        $curl = new Curl();
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieVerify);
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
        return $imagePath;
    }

    /**
        * 登录
        *
        * @return 
     */
    public function login()
    {
        $verify = rtrim($_POST["randCode"],',');
        if ( empty($verify) ) {
            echo "\n\n";
            var_export("数据为空,请重新请求");
            echo "\n\n";
            //return redirect('index');
        }

        // 校验验证码
        $captchaResult = $this->checkCha($verify);
        // 验证码验证成功
        if ( isset($captchaResult['result_code']) && $captchaResult['result_code']==4 ) {
            // 登录用户
            $loginResult = $this->loginToTrain();
            // 如果模拟登录成功
            if ( isset($loginResult['result_code']) && $loginResult['result_code']==0 ) {
                echo "成功<br\>";
            } else {
                echo "失败";
            }
        } else {
            echo "验证码错误<br\>";
            echo "<br\><br\>";
            var_export($captchaResult);
            echo "<br\><br\>";
            //return redirect('index');
        }
    }

    /**
        * 校验验证码
        *
        * @return 
     */
    private function checkCha($verify)
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;

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
        $curl->close();
        $captchaResult = json_decode(json_encode($curl->response), true);
        return $captchaResult;
    }

    /**
        * 模拟登录到12306
        *
        * @param $userName 用户名
        * @param $pwd 用户密码
        * @param $appid 对应设置字符串(固定)
        *
        * @return array
     */
    private function loginToTrain($userName="348977791@qq.com", $pwd="qq6661726", $appid="otn")
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;
        // 成功则模拟登录
        $urls = "https://kyfw.12306.cn/passport/web/login"; 
        $datas = [
            'username' => $userName,
            'password' => $pwd,
            'appid' => $appid
        ];
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_POST, 1);
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_POSTFIELDS, $datas);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $curl->post($urls, $datas);
        $loginResult = json_decode(json_encode($curl->response), true);
        return $loginResult;
    }

    /**
        * 获取用户信息
        *
        * @return string
     */
    public function getUserInfo()
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;

        $data = [
            '_json_att' => ''
        ];
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
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
        $curl->post("https://kyfw.12306.cn/otn/passengers/init", $data);
        $curl->close();
        $userInfoString = $curl->response;
        echo $userInfoString;
        $ggStatus = strpos($userInfoString, "朱雁宗");
        echo "\n\n";
        var_export($ggStatus);
        echo "\n\n";
        exit;
    }
}
