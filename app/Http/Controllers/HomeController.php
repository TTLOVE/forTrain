<?php

namespace App\Http\Controllers;
use \Curl\Curl;
use Input;

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
        // 打开主页获取cookie
        $indexUrl = "https://kyfw.12306.cn/otn/login/init";
        $this->getData($indexUrl);
        $savePath = "storage/framework/cache/image.jpg";
        $imageUrl = "https://kyfw.12306.cn/passport/captcha/captcha-image?login_site=E&module=login&rand=sjrand";
        $imagePath = $this->getPicAndSave($imageUrl, $savePath);
        return view('login')->with('imgUrl', $imagePath);
    }

    /**
        * 登录
        *
        * @return 
     */
    public function login()
    {
        $verify = rtrim($_POST["randCode"],',');
        $comeFrom = rtrim($_POST["comeFrom"],'login');
        if ( empty($verify) ) {
            echo "\n\n";
            var_export("数据为空,请重新请求");
            echo "\n\n";
            return redirect('index');
        }

        // 校验验证码
        $checkChaData = [
            'login_site' =>'E',
            'answer' => $verify,
            'rand' => 'sjrand',
            '_json_att' => ''
        ];
        $checkChaUrl = "https://kyfw.12306.cn/passport/captcha/captcha-check";
        $captchaResult = $this->postData($checkChaUrl, $checkChaData);
        // 验证码验证成功
        if ( isset($captchaResult['result_code']) && $captchaResult['result_code']==4 ) {
            // 登录用户
            $loginUrl = "https://kyfw.12306.cn/passport/web/login"; 
            $loginData = [
                'username' => "348977791@qq.com",
                'password' => "qq6661726",
                'appid' => "otn",
                '_json_att' => ''
            ];
            $loginResult = $this->postData($loginUrl, $loginData);
            // 如果模拟登录成功
            if ( isset($loginResult['result_code']) && $loginResult['result_code']==0 ) {
                // 去登录页面
                $loginAUrl = "https://kyfw.12306.cn/otn/login/userLogin";
                $loginAData = ['_json_att'=>''];
                $loginAResultData = $this->postData($loginAUrl, $loginAData);
                $loginBUrl = "https://kyfw.12306.cn/otn/index/initMy12306";
                $loginBResultData = $this->getData($loginBUrl);
                // 模拟下单
                return redirect('addOrderNow');
            } else {
                echo "失败";
            }
        } else {
            echo "验证码错误<br\>";
            echo "<br\><br\>";
            var_export($captchaResult);
            echo "<br\><br\>";
            return redirect('index');
        }
    }

    /**
        * 添加订单
        *
        * @return array
     */
    public function addOrder()
    {
        //$dateTime = "2017-11-24";
        //$from = "GZQ";
        //$to = "MDQ";
        //$trainListUrl = "https://kyfw.12306.cn/otn/leftTicket/query?leftTicketDTO.train_date=" . $dateTime . 
            //"&leftTicketDTO.from_station=" . $from . "&leftTicketDTO.to_station=" . $to . "&purpose_codes=ADULT";
        //$trainList = $this->getData($trainListUrl);
        //$trains = [];
        //if ( isset($trainList['status']) && $trainList['status']==true  && isset($trainList['data']['result']) && !empty($trainList['data']['result'])) {
            //$trainArray = $trainList['data']['result'];
            //foreach ($trainArray as $key => $train) {
                //$trainData = explode("|", $train);
                //$trains[] = [
                    //'train' => $trainData[3],
                    //'num' => $trainData[29]
                //];
            //}
        //}
        //echo "\n\n";
        //var_export($trains);
        //echo "\n\n";
        //exit;

        // 检验用户信息https://kyfw.12306.cn/otn/login/checkUser post  _json_att=''
        $checkUserData = [
            '_json_att' => ''
        ];
        $checkUserUrl = "https://kyfw.12306.cn/otn/login/checkUser";
        $checkUserReturnData = $this->getData($checkUserUrl);
        if ( isset($checkUserReturnData['status']) && $checkUserReturnData['status']==true  && isset($checkUserReturnData['data']['flag']) && $checkUserReturnData['data']['flag']==true ) {
            // 验证用户是登录状态的
            echo "\n\n";
            var_export($checkUserReturnData);
            echo "\n\n";
            exit;
        } else {
            // 没有登录,回到登录界面，带上来源
            return redirect('index')->with('comeFrom', 'addOrder');
        }
        // 下单https://kyfw.12306.cn/otn/confirmPassenger/checkOrderInfo post cancel_flag=2&bed_level_order_num=000000000000000000000000000000&passengerTicketStr=3%2C0%2C1%2C%E6%9C%B1%E9%9B%81%E5%AE%97%2C1%2C440981199209200231%2C13672476388%2CN_3%2C0%2C1%2C%E7%A8%8B%E6%81%92%2C1%2C440981199201181128%2C13672476388%2CN&oldPassengerStr=%E6%9C%B1%E9%9B%81%E5%AE%97%2C1%2C440981199209200231%2C1_%E7%A8%8B%E6%81%92%2C1%2C440981199201181128%2C1_&tour_flag=dc&randCode=&_json_att=&REPEAT_SUBMIT_TOKEN=2f6eab128238a7894ffd46a72c88a35a
        // 确认下单https://kyfw.12306.cn/otn/confirmPassenger/confirmSingle post passengerTicketStr=1%2C0%2C1%2C%E6%9C%B1%E9%9B%81%E5%AE%97%2C1%2C440981199209200231%2C13672476388%2CN_1%2C0%2C1%2C%E7%A8%8B%E6%81%92%2C1%2C440981199201181128%2C13672476388%2CN&oldPassengerStr=%E6%9C%B1%E9%9B%81%E5%AE%97%2C1%2C440981199209200231%2C1_%E7%A8%8B%E6%81%92%2C1%2C440981199201181128%2C1_&tour_flag=dc&randCode=&purpose_codes=00&key_check_isChange=AF4B4DEA6896FE1954876A604903A980D801DA67BA66C24AAB4CD22A&train_location=T3&choose_seats=&seatDetailType=000&roomType=00&dwAll=N&_json_att=&REPEAT_SUBMIT_TOKEN=188553c54279963dda120f800582616f
    }

    public function addOrderNow()
    {
        // 校验用户登录，获取新的key
        $checkLoginData = $this->postData("https://kyfw.12306.cn/passport/web/auth/uamtk", ['appid'=>'otn']);
        // 验证这个客户端可以登录
        $checkClient = $this->postData("https://kyfw.12306.cn/otn/uamauthclient", ['_json_att'=>'', 'tk'=>$checkLoginData['newapptk']]);
        // 检验用户信息https://kyfw.12306.cn/otn/login/checkUser post  _json_att=''
        $checkUserData = [
            '_json_att' => ''
        ];
        $checkUserUrl = "https://kyfw.12306.cn/otn/login/checkUser";
        $checkUserReturnData = $this->getData($checkUserUrl);
        echo "<br><br>";
        echo $checkUserUrl;
        echo "<br><br>";
        var_export($checkUserReturnData);
        echo "<br><br>";

        // 查看火车余票
        $dateTime = "2017-11-29";
        $from = "GZQ";
        $to = "MDQ";
        // log请求
        $trainLogUrl = "https://kyfw.12306.cn/otn/leftTicket/log?leftTicketDTO.train_date=" . $dateTime .
            "&leftTicketDTO.from_station=" . $from . "&leftTicketDTO.to_station=" . $to . "&purpose_codes=ADULT";
        $trainLogData = $this->getData($trainLogUrl);
        // 余票列表查询
        $trainListUrl = "https://kyfw.12306.cn/otn/leftTicket/query?leftTicketDTO.train_date=" . $dateTime . 
            "&leftTicketDTO.from_station=" . $from . "&leftTicketDTO.to_station=" . $to . "&purpose_codes=ADULT";
        $trainList = $this->getData($trainListUrl);
        $trains = [];
        if ( isset($trainList['status']) && $trainList['status']==true  && isset($trainList['data']['result']) && !empty($trainList['data']['result'])) {
            $trainArray = $trainList['data']['result'];
            foreach ($trainArray as $key => $train) {
                $trainData = explode("|", $train);
                $ticketKey = $trainData[29];
                if ( $ticketKey=="有" || $ticketKey>2 ) {
                    $trains[] = [
                        'train' => $trainData[3],
                        'num' => $trainData[29],
                        'carStr' => urldecode($trainData[0]),
                        'trainNo' => $trainData[2],
                        'leftTicket' => $trainData[12],
                        'train_location' => $trainData[15],
                    ];
                }
            }
        }
        echo $trainListUrl;
        echo "<br><br>";
        var_export($trains[0]);
        echo "<br><br>";

        // 获取乘客买票验证码
        $imageUrl = "https://kyfw.12306.cn/otn/passcodeNew/getPassCodeNew?module=passenger&rand=randp&0.757505074609071";
        $savePath = "storage/framework/cache/image.jpg";
        $imagePath = $this->getPicAndSave($imageUrl, $savePath);

        // 预提交订单
        $preAddOrderUrl = "https://kyfw.12306.cn/otn/leftTicket/submitOrderRequest";
        $preAddOrderQuery = [
            'secretStr' => $trains[0]['carStr'],
            'train_date' => $dateTime,
            'back_train_date' => '2017-11-24',
            'tour_flag' => 'dc',
            'purpose_codes' => 'ADULT',
            'query_from_station_name' => '广州',
            'query_to_station_name' => '茂名',
            'undefined' => ''
        ];
        $preAddOrderData = $this->postData($preAddOrderUrl, $preAddOrderQuery);
        echo $preAddOrderUrl;
        echo "<br><br>";
        var_export($preAddOrderData);
        echo "<br><br>";

        // 初始化页面,获取token
        $initUrl = "https://kyfw.12306.cn/otn/confirmPassenger/initDc";
        $initQuery = ['_json_att'=>''];
        $initData = $this->postData($initUrl, $initQuery);
        preg_match("/var globalRepeatSubmitToken = '(.*?)';/", $initData, $theTokenArray);
        preg_match("/'key_check_isChange':'(.*?)'/", $initData, $keyCheckArray);
        $theToken = $theTokenArray[1];
        $key_check_isChange = $keyCheckArray[1];

        // 获取我的常用联系人列表
        $getPassengerListQuery = [
            '_json_att' => '',
            'REPEAT_SUBMIT_TOKEN' => $theToken
        ];
        $getPassengerListUrl = "https://kyfw.12306.cn/otn/confirmPassenger/getPassengerDTOs";
        $passengerList = $this->postData($getPassengerListUrl, $getPassengerListQuery);
        echo $getPassengerListUrl;
        echo "<br><br>";
        var_export($getPassengerListQuery);
        echo "<br><br>";
        var_export($passengerList);
        echo "<br><br>";

        // 购票人确定
        // passengerTicketStr组成的格式：seatType,0,票类型（成人票填1）,乘客名,passenger_id_type_code,passenger_id_no,mobile_no,’N’
        // 座位编号（seatType）参考：‘硬卧’ => ‘3’,‘软卧’ => ‘4’,‘二等座’ => ‘O’,‘一等座’ => ‘M’,‘硬座’ => ‘1’,多个乘车人用’_’隔开
        // oldPassengerStr组成的格式：乘客名,passenger_id_type_code,passenger_id_no,passenger_type，’_’
        // 多个乘车人用’_’隔开，注意最后的需要多加一个’_’。
        $confirmPassengerUrl = "https://kyfw.12306.cn/otn/confirmPassenger/checkOrderInfo";
        $confirmPassengerQuery = [
            'cancel_flag' => '2',
            'bed_level_order_num' => '000000000000000000000000000000',
            'passengerTicketStr' => '1,0,1,朱雁宗,1,440981199209200231,13672476388,N_1,0,1,程恒,1,440981199201181128,13672476388,N',
            'oldPassengerStr' => '朱雁宗,1,440981199209200231,1_程恒,1,440981199201181128,1_',
            'tour_flag' => 'dc',
            'randCode' => '',
            '_json_att' => '',
            'REPEAT_SUBMIT_TOKEN' => $theToken
        ];
        echo $confirmPassengerUrl;
        echo "<br><br>";
        var_export($confirmPassengerQuery);
        echo "<br><br>";
        $confirmPassengerData = $this->postData($confirmPassengerUrl, $confirmPassengerQuery);
        echo "<br><br>";
        var_export($confirmPassengerData);
        echo "<br><br>";
        // 如果申请成功，往下走，失败的话，重新登录
        if ( isset($confirmPassengerData['status']) && $confirmPassengerData['status']==true && isset($confirmPassengerData['data']['submitStatus']) && $confirmPassengerData['data']['submitStatus']==true ) {
            // todo,如果要提交验证码则加上验证码接口，获取是否要提交验证码的判断
            $ifShowPassCode = $confirmPassengerData['data']['ifShowPassCode'];

            // 查看排队人数
            $queueCountUrl = "https://kyfw.12306.cn/otn/confirmPassenger/getQueueCount";
            $queueCountQuery = [
                'train_date' => gmdate("D M d Y 00:00:00", strtotime($dateTime)) . " GMT+0800 (CST)",
                'train_no' => $trains[0]['trainNo'],
                'stationTrainCode' => $trains[0]['train'],
                'seatType' => '1',
                'fromStationTelecode' => $from,
                'toStationTelecode' => $to,
                'leftTicket' => $trains[0]['leftTicket'],
                'purpose_codes' => '00',
                'train_location' => $trains[0]['train_location'],
                '_json_att' => '',
                'REPEAT_SUBMIT_TOKEN' => $theToken
            ];
            $queueCountData = $this->postData($queueCountUrl, $queueCountQuery);
            echo $queueCountUrl;
            echo "<br><br>";
            var_export($queueCountQuery);
            echo "<br><br>";
            var_export($queueCountData);
            echo "<br><br>";
            if ( isset($queueCountData['status']) && $queueCountData['status']==true ) {
                // 确认订单 
                $confirmOrderUrl = "https://kyfw.12306.cn/otn/confirmPassenger/confirmSingleForQueue";
                $confirmOrderQuery = [
                    'passengerTicketStr' => '1,0,1,朱雁宗,1,440981199209200231,13672476388,N_1,0,1,程恒,1,440981199201181128,13672476388,N',
                    'oldPassengerStr' => '朱雁宗,1,440981199209200231,1_程恒,1,440981199201181128,1_',
                    'randCode' => '',
                    'purpose_codes' => '00',
                    'key_check_isChange' => $key_check_isChange,
                    'leftTicketStr' => $trains[0]['leftTicket'],
                    'train_location' => $trains[0]['train_location'],
                    'choose_seats' => '',
                    //'choose_seats' => $confirmPassengerData['data']['choose_Seats'],
                    'seatDetailType' => '000',
                    'roomType' => '00',
                    'dwAll' => 'N',
                    '_json_att' => '',
                    'REPEAT_SUBMIT_TOKEN' => $theToken
                ];
                $confirmOrderData = $this->postData($confirmOrderUrl, $confirmOrderQuery);
                if ( isset($confirmOrderData['status']) && $confirmOrderData['status']==true && isset($confirmOrderData['data']['submitStatus']) && $confirmOrderData['data']['submitStatus']==true ) {
                    $orderId = '';
                    while( empty($orderId) ) {
                        $getAddOrderWaitTimeUrl = "https://kyfw.12306.cn/otn/confirmPassenger/queryOrderWaitTime?random=" . time() . 
                            "&tourFlag=dc&_json_att=&REPEAT_SUBMIT_TOKEN=" . $theToken;
                        $getAddOrderWaitTimeData = $this->getData($getAddOrderWaitTimeUrl);
                        if ( empty($getAddOrderWaitTimeData['data']['orderId']) ) {
                            echo "sleep 2s";
                            sleep(2);
                            $orderId = '';
                            echo "<br><br>";
                            var_export("fail");
                            echo "<br><br>";
                            var_export($getAddOrderWaitTimeData);
                            echo "<br><br>";
                        } else {
                            $orderId = $getAddOrderWaitTimeData['data']['orderId'];
                        }
                    }
                    exit("success " . $orderId);
                } else {
                    // todo 重新登录
                    //return redirect("index");
                    echo $confirmOrderUrl;
                    echo "<br><br>";
                    var_export($confirmOrderQuery);
                    echo "<br><br>";
                    var_export($confirmOrderData);
                    echo "<br><br>";
                    exit();
                }
            } else {
                // todo 重新登录
                //return redirect("index");
                echo $queueCountUrl;
                echo "<br><br>";
                var_export($queueCountQuery);
                echo "<br><br>";
                var_export($queueCountData);
                echo "<br><br>";
                exit();
            }
        } else {
            // todo 重新登录
            //return redirect("index");
            echo "<br><br>";
            var_export($confirmPassengerData);
            echo "<br><br>";
            exit;
        }
        exit;
    }

    /**
        * 获取用户信息
        *
        * @return string
     */
    public function getTrainList()
    {
        //$dateTime = date("Y-m-d");
        $dateTime = "2017-11-23";
        $from = "GZQ";
        $to = "MDQ";
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;

        // 请求log
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $url = "https://kyfw.12306.cn/otn/leftTicket/log?leftTicketDTO.train_date=".$dateTime."&leftTicketDTO.from_station=".$from."&leftTicketDTO.to_station=".$to."&purpose_codes=ADULT";
        $curl->get($url);
        $curl->close();

        // 请求query
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $url = "https://kyfw.12306.cn/otn/leftTicket/query?leftTicketDTO.train_date=".$dateTime."&leftTicketDTO.from_station=".$from."&leftTicketDTO.to_station=".$to."&purpose_codes=ADULT";
        $curl->get($url);
        $curl->close();
        $trainList = json_decode(json_encode($curl->response), true);
        return $trainList;
    }

    /**
        * 通过get方法获取数据信息
        *
        * @param $url　对应链接
        *
        * @return array
     */
    private function getData($url)
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;
        // 请求query
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        date_default_timezone_set('PRC');//设置时区                               
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $curl->get($url);
        $curl->close();
        return json_decode(json_encode($curl->response), true);
    }

    /**
        * 获取图片并存储在对应位置，返回对应存储位置
        *
        * @param $imageUrl 图片链接
        * @param $savePath 保存路径
        *
        * @return string
     */
    private function getPicAndSave($imageUrl, $savePath)
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;
        $fp = fopen($savePath,'wb');//把请求的图片二进制文件写入image.jpg中
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
        $curl->get($imageUrl);
        $curl->close();
        fclose($fp);
        return $savePath;
    }

    /**
        * 通过post方法获取数据信息
        *
        * @param $url 请求链接
        * @param $postData 请求数据
        *
        * @return array
     */
    private function postData($url, $postData)
    {
        $cookieVerify = self::COOKIE_VERIFY;
        $cookieSuccess = self::COOKIE_SUCCESS;
        // 申请post数据
        $curl = new Curl();
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true); 
        $curl->setOpt(CURLOPT_COOKIEFILE, $cookieSuccess);
        $curl->setOpt(CURLOPT_HEADER, 0); 
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 120); 
        $curl->setOpt(CURLOPT_POST, 1);
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
        $curl->setOpt(CURLOPT_POSTFIELDS, $postData);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);            //终止从服务端进行验证
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36');
        $curl->setOpt(CURLOPT_HTTPHEADER, 
            array("application/x-www-form-urlencoded; charset=utf-8"));
        $curl->post($url, $postData);
        $curl->close();
        return json_decode(json_encode($curl->response), true);
    }
}
