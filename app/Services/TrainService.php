<?php

namespace App\Services;

use \Curl\Curl;
use App\Services\DaMaService;
use Mail;

class TrainService 
{
    CONST COOKIE_VERIFY = "/tmp/verify.tmp";
    CONST COOKIE_SUCCESS = "/tmp/verify.tmp";
    //CONST THE_TRAIN_ARRAY = [
        //'K587',
        //'K837',
        //'K4224',
        //'K9045',
        //'K9049',
    //];
    CONST THE_TRAIN_ARRAY = [];
    /**
     * 最低剩余票数
     */
    CONST MIN_NUM = 3;
    private $theDate = "";

    public function __construct($date='')
    {
        $this->theDate = empty($date) ? '2018-01-28' : $date;
    }

    /**
     * 获取列表
     *
     * @return array
     */
    public function searchTrainList()
    {
        $allTrains = [];
        $trains = [];
        $trainList = $this->getTrainList();
        if (isset($trainList['data']['result']) && !empty($trainList['data']['result'])) {
            $trainArray = $trainList['data']['result'];
            foreach ($trainArray as $key => $train) {
                $trainData = explode("|", $train);
                $allTrains[] = [
                    'train' => $trainData[3],
                    'num' => $trainData[29],
                    'carStr' => urldecode($trainData[0]),
                    'trainNo' => $trainData[2],
                    'leftTicket' => $trainData[12],
                    'train_location' => $trainData[15],
                    'begin' => $trainData[8],
                    'end' => $trainData[9],
                ];
                $ticketKey = $trainData[29];
                if ( $ticketKey=="有" || $ticketKey>=self::MIN_NUM ) {
                    if ( empty(self::THE_TRAIN_ARRAY) || (!empty(self::THE_TRAIN_ARRAY) &&  in_array($trainData[3], self::THE_TRAIN_ARRAY)) ) {
                        $trains[] = [
                            'train' => $trainData[3],
                            'num' => $trainData[29],
                            'carStr' => urldecode($trainData[0]),
                            'trainNo' => $trainData[2],
                            'leftTicket' => $trainData[12],
                            'train_location' => $trainData[15],
                            'begin' => $trainData[8],
                            'end' => $trainData[9],
                        ];
                    }
                }
            }
            if ( empty($trains) ) {
                $returnData = [
                    'status' => 0,
                    'data' => $allTrains
                ];
            } else {
                $returnData = [
                    'status' => 1,
                    'data' => $trains
                ];
            }
        } else {
            $returnData = [
                'status' => 0,
                'msg' => '获取失败',
                'data' => []
            ];
        }
        return $returnData;
    }

    /**
     * 登录验证码输入页面
     *
     */
    public function getLoginPic()
    {
        // 打开主页获取cookie
        $indexUrl = "https://kyfw.12306.cn/otn/login/init";
        $this->getData($indexUrl);
        $savePath = "storage/framework/cache/image.jpg";
        $imageUrl = "https://kyfw.12306.cn/passport/captcha/captcha-image?login_site=E&module=login&rand=sjrand&0.15911311656759652";
        $imagePath = $this->getPicAndSave($imageUrl, $savePath);
        return $imagePath;
    }

    /**
     * 获取图片密码
     *
     * @param $picPath 图片路径
     *
     * @return array
     */
    public function getPicMap($picPath)
    {
        $DaMaService = new DaMaService('TT_LOVE', 'zyz6673777');
        $balanceInfo = $DaMaService->getBalance();
        echo "\n\n";
        var_export($balanceInfo);
        echo "\n\n";
        if ( isset($balanceInfo['ret']) && $balanceInfo['ret']==0 && isset($balanceInfo['balance']) && $balanceInfo['balance']<=150 ) {
            $this->sendEmail("剩余余额不足啦！余额：". $balanceInfo['balance']);
            return false;
        }

        $picResult = $DaMaService->decodeBase64($picPath, 287);
        return $picResult;
    }

    /**
     * 校验验证码
     *
     * @param $verify　验证码信息
     *
     * @return array
     */
    public function checkCha($verify)
    {
        // 校验验证码
        $checkChaData = [
            'login_site' =>'E',
            'answer' => $verify,
            'rand' => 'sjrand'
        ];
        $checkChaUrl = "https://kyfw.12306.cn/passport/captcha/captcha-check";
        $captchaResult = $this->postData($checkChaUrl, $checkChaData);
        $retryTime = 1;
        while ($retryTime<=10 && 
            (!isset($captchaResult['result_code']) || (isset($captchaResult['result_code']) && $captchaResult['result_code']!=4))) {
                echo "\n";
                var_export("验证码校验失败，第" . $retryTime . "次");
                echo "\n";
                var_export($captchaResult);
                echo "\n";
                var_export("休息80毫秒");
                echo "\n";
                usleep(80000);
                $captchaResult = $this->postData($checkChaUrl, $checkChaData);
                $retryTime++;
            }
        
        if ( isset($captchaResult['result_code']) && $captchaResult['result_code']==4 ) {
            $returnData = [
                'status' => 1,
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function checkChaReportError($id)
    {
        $DaMaService = new DaMaService('TT_LOVE', 'zyz6673777');
        $errorResult = $DaMaService->reportError($id);
        return $errorResult;
    }

    /**
     * 用户登录
     *
     * @return array
     */
    public function userLogin()
    {
        // 登录用户
        $loginUrl = "https://kyfw.12306.cn/passport/web/login"; 
        $loginData = [
            //'username' => "348977791@qq.com",
            //'password' => "qq6661726",
            'username' => "15915863474",
            'password' => "chengjianhao613",
            'appid' => "otn",
            '_json_att' => ''
        ];
        $loginResult = $this->postData($loginUrl, $loginData);
        $retryTime = 1;
        // 如果校验失败，重试10次
        while ($retryTime<=10 && 
            (!isset($loginResult['result_code']) || (isset($loginResult['result_code']) && $loginResult['result_code']!=0))) {
                var_export("登录校验失败，第" . $retryTime . "次");
                echo "\n";
                var_export($loginResult);
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $loginResult = $this->postData($loginUrl, $loginData);
                $retryTime++;
            }

        // 如果10次都失败的话重新回到校验验证码页面
        if (isset($loginResult['result_code']) && $loginResult['result_code']==0) {
            $returnData = [
                'status' => 1,
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function loginHome()
    {
        // 去登录页面
        $loginAUrl = "https://kyfw.12306.cn/otn/passport?redirect=/otn/login/userLogin";
        $loginAData = ['_json_att'=>''];
        $loginAResultData = $this->postData($loginAUrl, $loginAData);
        $loginBUrl = "https://kyfw.12306.cn/otn/index/initMy12306";
        $loginBResultData = $this->getData($loginBUrl);
    }

    public function checkUser()
    {
        // 校验用户登录，获取新的key
        $checkLoginData = $this->postData("https://kyfw.12306.cn/passport/web/auth/uamtk", ['appid'=>'otn']);
        $times = 1;
        // 如果校验失败，重试
        while ($times<=3 && 
            (!isset($checkLoginData['result_code']) || (isset($checkLoginData['result_code']) && $checkLoginData['result_code']!=0))) {
                var_export("校验失败，第" . $times . "次");
                echo "\n";
                var_export($checkLoginData);
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $checkLoginData = $this->postData("https://kyfw.12306.cn/passport/web/auth/uamtk", ['appid'=>'otn']);
                $times++;
            }

        // 如果都失败的话重新回到校验验证码页面
        if (isset($checkLoginData['result_code']) && $checkLoginData['result_code']==0) {
            $returnData = [
                'status' => 1,
                'data' => $checkLoginData
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function checkUserLoginStatus($newapptk)
    {
        // 验证这个客户端可以登录
        $checkClient = $this->postData("https://kyfw.12306.cn/otn/uamauthclient", ['_json_att'=>'', 'tk'=>$newapptk]);
        $times = 1;
        // 如果校验失败，重试
        while ($times<=3 && 
            (!isset($checkClient['result_code']) || (isset($checkClient['result_code']) && $checkClient['result_code']!=0))) {
                var_export("校验失败，第" . $times . "次");
                echo "\n";
                var_export($checkClient);
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $checkClient = $this->postData("https://kyfw.12306.cn/otn/uamauthclient", ['_json_att'=>'', 'tk'=>$newapptk]);
                $times++;
            }

        // 如果都失败的话重新回到校验验证码页面
        if (isset($checkClient['result_code']) && $checkClient['result_code']==0) {
            $returnData = [
                'status' => 1,
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function checkUserData()
    {
        // 检验用户信息
        $checkUserData = [
            '_json_att' => ''
        ];
        $checkUserUrl = "https://kyfw.12306.cn/otn/login/checkUser";
        $checkUserReturnData = $this->getData($checkUserUrl);
        $times = 1;
        // 如果校验失败，重试
        while ($times<=3 && 
            (!isset($checkUserReturnData['data']['flag']) || (isset($checkUserReturnData['data']['flag']) && $checkUserReturnData['data']['flag']!=true))) {
                var_export("校验失败，第" . $times . "次");
                echo "\n";
                var_export($checkUserReturnData);
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $checkUserReturnData = $this->getData($checkUserUrl);
                $times++;
            }

        // 如果都失败的话重新回到校验验证码页面
        if (isset($checkUserReturnData['data']['flag']) && $checkUserReturnData['data']['flag']==true) {
            $returnData = [
                'status' => 1,
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function checkTicket()
    {
        // 查看火车余票
        $dateTime = $this->theDate;
        $from = "GZQ";
        $to = "MDQ";
        $trainList = $this->getTrainList();
        $times = 1;
        // 如果校验失败，重试
        while ($times<=10 && 
            (!isset($trainList['data']['result']) || (isset($trainList['data']['result']) && empty($trainList['data']['result'])))) {
                var_export("校验失败，第" . $times . "次");
                echo "\n";
                var_export($trainList);
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $trainList = $this->getTrainList();
                $times++;
            }
        $trains = [];
        // 如果都失败的话重新回到校验验证码页面
        if (isset($trainList['data']['result']) && !empty($trainList['data']['result'])) {
            if ( isset($trainList['status']) && $trainList['status']==true  && isset($trainList['data']['result']) && !empty($trainList['data']['result'])) {
                $trainArray = $trainList['data']['result'];
                foreach ($trainArray as $key => $train) {
                    $trainData = explode("|", $train);
                    $ticketKey = $trainData[29];
                    if ( $ticketKey=="有" || $ticketKey>=self::MIN_NUM ) {
                        if ( empty(self::THE_TRAIN_ARRAY) || (!empty(self::THE_TRAIN_ARRAY) &&  in_array($trainData[3], self::THE_TRAIN_ARRAY)) ) {
                            $trains[] = [
                                'train' => $trainData[3],
                                'num' => $trainData[29],
                                'carStr' => urldecode($trainData[0]),
                                'trainNo' => $trainData[2],
                                'leftTicket' => $trainData[12],
                                'train_location' => $trainData[15],
                                'begin' => $trainData[8],
                                'end' => $trainData[9],
                            ];
                        }
                    }
                }
            }
        }
        if ( !empty($trains) ) {
            $returnData = [
                'status' => 1,
                'data' => $trains
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function getOrderPic()
    {
        // 获取乘客买票验证码
        $imageUrl = "https://kyfw.12306.cn/otn/passcodeNew/getPassCodeNew?module=passenger&rand=randp&0.757505074609071";
        $savePath = "storage/framework/cache/image.jpg";
        $imagePath = $this->getPicAndSave($imageUrl, $savePath);
        return $imagePath;
    }

    /**
     * 校验验证码
     *
     * @param $verify　验证码信息
     *
     * @return array
     */
    public function checkOrderCha($verify, $theToken)
    {
        // 校验验证码
        $checkChaData = [
            'REPEAT_SUBMIT_TOKEN' => $theToken,
            'randCode' => $verify,
            'rand' => 'randp',
            '_json_att' => ''
        ];
        $checkChaUrl = "https://kyfw.12306.cn/otn/passcodeNew/checkRandCodeAnsyn";
        $captchaResult = $this->postData($checkChaUrl, $checkChaData);
        $retryTime = 1;
        while ($retryTime<=10 && 
            (!isset($captchaResult['result_code']) || (isset($captchaResult['result_code']) && $captchaResult['result_code']!=4))) {
                echo "\n";
                var_export("验证码校验失败，第" . $retryTime . "次");
                echo "\n";
                var_export($captchaResult);
                echo "\n";
                var_export("休息80毫秒");
                echo "\n";
                usleep(80000);
                $captchaResult = $this->postData($checkChaUrl, $checkChaData);
                $retryTime++;
            }
        
        if ( isset($captchaResult['result_code']) && $captchaResult['result_code']==4 ) {
            $returnData = [
                'status' => 1,
            ];
        } else {
            // 如果10次都失败的话重新回到校验验证码页面
            $returnData = [
                'status' => -1,
            ];
        }
        return $returnData;
    }

    public function dealOrderCode($theToken)
    {
        $verify = '';
        $checkChaFlag = true;
        $times = 1;
        while ($checkChaFlag && $times<=10) {
            $savePic = $TrainService->getOrderPic();
            $imgPath = "/usr/local/nginx/html/forTrain/" . $savePic;
            $picResult = $this->getPicMap($imgPath);
            if ( isset($picResult) && $picResult['ret']==0 ) {
                echo "\n";
                var_export('第' . $times . '次校验验证码');
                echo "\n";
                $verify = str_replace("|", ",", $picResult['result']);
                $checkResult = $this->checkOrderCha($verify, $theToken);
                if ( $checkResult['status']==1 ) {
                    $checkChaFlag = false;
                    var_export('校验验证码成功');
                    echo "\n";
                } else {
                    $this->checkChaReportError($picResult['id']);
                    var_export('校验验证码失败');
                    echo "\n";
                }
            } else {
                var_export('获取验证码密码失败');
                echo "\n";
            }
            $times++;
        }
        return $verify;
    }

    public function addOrder($trains)
    {
        echo "\n";
        var_export("预提交订单");
        echo "\n";
        $from = "GZQ";
        $to = "MDQ";
        // 循环选择特定的车次
        foreach ($trains as $train) {
            echo "\n";
            var_export("获取乘客买票验证码");
            echo "\n";
            // 获取乘客买票验证码
            $imageUrl = "https://kyfw.12306.cn/otn/passcodeNew/getPassCodeNew?module=passenger&rand=randp&0.757505074609071";
            $savePath = "storage/framework/cache/image.jpg";
            $imagePath = $this->getPicAndSave($imageUrl, $savePath);
            // 预提交订单
            $preAddOrderUrl = "https://kyfw.12306.cn/otn/leftTicket/submitOrderRequest";
            $preAddOrderQuery = [
                'secretStr' => $train['carStr'],
                'train_date' => $this->theDate,
                'back_train_date' => $this->theDate,
                'tour_flag' => 'dc',
                'purpose_codes' => 'ADULT',
                'query_from_station_name' => '广州',
                'query_to_station_name' => '茂名',
                'undefined' => ''
            ];
            $preAddOrderData = $this->postData($preAddOrderUrl, $preAddOrderQuery);
            echo "\n";
            var_export("预提交订单返回数据");
            echo "\n";
            var_export($preAddOrderData);
            echo "\n";

            echo "\n";
            var_export("初始化页面,获取token");
            echo "\n";
            // 初始化页面,获取token
            $initUrl = "https://kyfw.12306.cn/otn/confirmPassenger/initDc";
            $initQuery = ['_json_att'=>''];
            $initData = $this->postData($initUrl, $initQuery);
            preg_match("/var globalRepeatSubmitToken = '(.*?)';/", $initData, $theTokenArray);
            preg_match("/'key_check_isChange':'(.*?)'/", $initData, $keyCheckArray);
            $theToken = $theTokenArray[1];
            $key_check_isChange = $keyCheckArray[1];
            $times = 1;
            // 如果校验失败，重试
            while ($times<=3 && (empty($theToken) || empty($key_check_isChange))) {
                var_export("校验失败，第" . $times . "次");
                echo "\n";
                var_export("休息50毫秒");
                echo "\n";
                usleep(50000);
                $initData = $this->postData($initUrl, $initQuery);
                preg_match("/var globalRepeatSubmitToken = '(.*?)';/", $initData, $theTokenArray);
                preg_match("/'key_check_isChange':'(.*?)'/", $initData, $keyCheckArray);
                $theToken = $theTokenArray[1];
                $key_check_isChange = $keyCheckArray[1];
                $times++;
            }
            // 如果都失败的话重新回到校验验证码页面
            if ( $times>3 && (empty($theToken) || empty($key_check_isChange))) {
                $returnData = [
                    'status' => 0,
                    'msg' => '获取token信息失败'
                ];
                return $returnData;
            }

            echo "\n";
            var_export("获取我的常用联系人列表");
            echo "\n";
            // 获取我的常用联系人列表
            $getPassengerListQuery = [
                '_json_att' => '',
                'REPEAT_SUBMIT_TOKEN' => $theToken
            ];
            $getPassengerListUrl = "https://kyfw.12306.cn/otn/confirmPassenger/getPassengerDTOs";
            $passengerList = $this->postData($getPassengerListUrl, $getPassengerListQuery);
            echo "\n";
            var_export("获取我的常用联系人列表返回数据");
            echo "\n";
            var_export($passengerList);
            echo "\n";

            echo "\n";
            var_export("购票人确定");
            echo "\n";
            // 购票人确定
            // passengerTicketStr组成的格式：seatType,0,票类型（成人票填1）,乘客名,passenger_id_type_code,passenger_id_no,mobile_no,’N’
            // 座位编号（seatType）参考：‘硬卧’ => ‘3’,‘软卧’ => ‘4’,‘二等座’ => ‘O’,‘一等座’ => ‘M’,‘硬座’ => ‘1’,多个乘车人用’_’隔开
            // oldPassengerStr组成的格式：乘客名,passenger_id_type_code,passenger_id_no,passenger_type，’_’
            // 多个乘车人用’_’隔开，注意最后的需要多加一个’_’。
            $confirmPassengerUrl = "https://kyfw.12306.cn/otn/confirmPassenger/checkOrderInfo";
            $confirmPassengerQuery = [
                'cancel_flag' => '2',
                'bed_level_order_num' => '000000000000000000000000000000',
                'passengerTicketStr' => '1,0,1,朱雁宗,1,440981199209200231,13672476388,N_1,0,1,程恒,1,440981199201181128,13672476388,N_1,0,1,程剑豪,1,440981199506131156,13672476388,N',
                'oldPassengerStr' => '朱雁宗,1,440981199209200231,1_程恒,1,440981199201181128,1_程剑豪,1,440981199506131156,1_',
                'tour_flag' => 'dc',
                'randCode' => '',
                '_json_att' => '',
                'REPEAT_SUBMIT_TOKEN' => $theToken
            ];
            $confirmPassengerData = $this->postData($confirmPassengerUrl, $confirmPassengerQuery);
            $times = 1;
            // 如果校验失败，重试
            while ($times<=5 && 
                (!isset($confirmPassengerData['data']['submitStatus']) || (isset($confirmPassengerData['data']['submitStatus']) && $confirmPassengerData['data']['submitStatus']!=true))) {
                    var_export("校验失败，第" . $times . "次");
                    echo "\n";
                    var_export("休息50毫秒");
                    echo "\n";
                    usleep(50000);
                    $confirmPassengerData = $this->postData($confirmPassengerUrl, $confirmPassengerQuery);
                    $times++;
                }
            // 如果都失败的话重新回到校验验证码页面
            if ( $times>5 && 
                (!isset($confirmPassengerData['data']['submitStatus']) || (isset($confirmPassengerData['data']['submitStatus']) && $confirmPassengerData['data']['submitStatus']!=true))) {
                    $returnData = [
                        'status' => 0,
                        'msg' => '购票人确定失败'
                    ];
                    return $returnData;
                }
            echo "\n";
            var_export("购票人确定成功，返回数据");
            echo "\n";

            echo "\n";
            var_export("申请成功，往下走,查看排队人数");
            echo "\n";
            // 查看排队人数
            $queueCountUrl = "https://kyfw.12306.cn/otn/confirmPassenger/getQueueCount";
            $queueCountQuery = [
                'train_date' => gmdate("D M d Y 00:00:00", strtotime($this->theDate)) . " GMT+0800 (CST)",
                    'train_no' => $train['trainNo'],
                    'stationTrainCode' => $train['train'],
                    'seatType' => '1',
                    'fromStationTelecode' => $from,
                    'toStationTelecode' => $to,
                    'leftTicket' => $train['leftTicket'],
                    'purpose_codes' => '00',
                    'train_location' => $train['train_location'],
                    '_json_att' => '',
                    'REPEAT_SUBMIT_TOKEN' => $theToken
                ];
            $queueCountData = $this->postData($queueCountUrl, $queueCountQuery);
            $times = 1;
            // 如果校验失败，重试
            while ($times<=5 && 
                (!isset($queueCountData['status']) || (isset($queueCountData['status']) && $queueCountData['status']==true))) {
                    var_export("校验失败，第" . $times . "次");
                    echo "\n";
                    var_export("休息50毫秒");
                    echo "\n";
                    usleep(50000);
                    $queueCountData = $this->postData($queueCountUrl, $queueCountQuery);
                    $times++;
                }
            // 如果都失败的话重新回到校验验证码页面
            if ( $times>5 && 
                (!isset($queueCountData['status']) || (isset($queueCountData['status']) && $queueCountData['status']==true))) {
                    $returnData = [
                        'status' => 0,
                        'msg' => '查看排队人数失败'
                    ];
                    return $returnData;
                }
            echo "\n";
            var_export("查看排队人数返回数据");
            echo "\n";

            // 申请成功，往下走
            $ifShowPassCode = $confirmPassengerData['data']['ifShowPassCode'];
            // 如果需要显示验证码提交,退出循环，跳去提交验证码
            $orderVerify = '';
            if ( $ifShowPassCode=='Y' ) {
                $orderVerify = $this->dealOrderCode($theToken);
                if ( empty($orderVerify) ) {
                    $returnData = [
                        'status' => 0,
                        'msg' => '校验订单二维码失败'
                    ];
                    return $returnData;
                }
            }

            echo "\n";
            var_export("确认订单");
            echo "\n";
            // 确认订单 
            $confirmOrderUrl = "https://kyfw.12306.cn/otn/confirmPassenger/confirmSingleForQueue";
            $confirmOrderQuery = [
                'passengerTicketStr' => '1,0,1,朱雁宗,1,440981199209200231,13672476388,N_1,0,1,程恒,1,440981199201181128,13672476388,N_1,0,1,程剑豪,1,440981199506131156,13672476388,N',
                'oldPassengerStr' => '朱雁宗,1,440981199209200231,1_程恒,1,440981199201181128,1_程剑豪,1,440981199506131156,1_',
                'randCode' => $orderVerify,
                'purpose_codes' => '00',
                'key_check_isChange' => $key_check_isChange,
                'leftTicketStr' => $train['leftTicket'],
                'train_location' => $train['train_location'],
                'choose_seats' => '',
                //'choose_seats' => $confirmPassengerData['data']['choose_Seats'],
                'seatDetailType' => '000',
                'roomType' => '00',
                'dwAll' => 'N',
                '_json_att' => '',
                'REPEAT_SUBMIT_TOKEN' => $theToken
            ];
            $confirmOrderData = $this->postData($confirmOrderUrl, $confirmOrderQuery);
            $times = 1;
            // 如果校验失败，重试
            while ($times<=5 && 
                (!isset($confirmOrderData['data']['submitStatus']) || (isset($confirmOrderData['data']['submitStatus']) && $confirmOrderData['data']['submitStatus']==true))) {
                    var_export("校验失败，第" . $times . "次");
                    echo "\n";
                    var_export("休息50毫秒");
                    echo "\n";
                    usleep(50000);
                    $confirmOrderData = $this->postData($confirmOrderUrl, $confirmOrderQuery);
                    $times++;
                }
            // 如果都失败的话重新回到校验验证码页面
            if ( $times>5 && 
                (!isset($confirmOrderData['data']['submitStatus']) || (isset($confirmOrderData['data']['submitStatus']) && $confirmOrderData['data']['submitStatus']==true))) {
                    $returnData = [
                        'status' => 0,
                        'msg' => '确认订单失败'
                    ];
                    return $returnData;
                }
            echo "\n";
            var_export("确认订单返回数据");
            echo "\n";

            $orderId = '';
            $times = 1;
            while( empty($orderId) && $times<100 ) {
                $getAddOrderWaitTimeUrl = "https://kyfw.12306.cn/otn/confirmPassenger/queryOrderWaitTime?random=" . time() . 
                    "&tourFlag=dc&_json_att=&REPEAT_SUBMIT_TOKEN=" . $theToken;
                $getAddOrderWaitTimeData = $this->getData($getAddOrderWaitTimeUrl);
                if ( empty($getAddOrderWaitTimeData['data']['orderId']) ) {
                    echo "\n";
                    var_export("失败,休息50毫秒");
                    echo "\n";
                    var_export($getAddOrderWaitTimeData);
                    echo "\n";
                    usleep(50000);
                } else {
                    $orderId = $getAddOrderWaitTimeData['data']['orderId'];
                    break;
                }
                $times++;
            }
            if ( empty($orderId) ) {
                continue;
            } else {
                $returnData = [
                    'status' => 1,
                    'msg' => '下单成功，订单id:' . $orderId
                ];
                return $returnData;
            }
        }
    }

    /**
     * 买票验证码输入页面
     *
     */
    public function addOrderCheck()
    {
        // 打开主页获取cookie
        $savePath = "storage/framework/cache/image.jpg";
        return view('addOrderCheck')->with('imgUrl', $imagePath);
    }

    /**
     * 处理验证码请求并直接下单
     */
    public function checkOrderCodeAndAddOrder()
    {
        $verify = rtrim($_POST["randCode"],',');
        $theToken = $_POST["theToken"];
        // 验证码为空，重新来过
        if ( empty($verify) || empty($theToken) ) {
            // 返回下单页面
            return redirect('addOrderNow');
        }

        // 校验验证码
        $checkChaData = [
            'login_site' =>'E',
            'answer' => $verify,
            'rand' => 'sjrand',
            '_json_att' => '',
            'REPEAT_SUBMIT_TOKEN' => $theToken
        ];
        $checkChaUrl = "https://kyfw.12306.cn/passport/captcha/captcha-check";
        $captchaResult = $this->postData($checkChaUrl, $checkChaData);
        // 验证码验证成功
        if ( isset($captchaResult['result_code']) && $captchaResult['result_code']==4 ) {
            // 直接下单
        } else {
            // 返回下单页面
            return redirect('addOrderNow');
        }
    }

    /**
     * 获取用户信息
     *
     * @return string
     */
    public function getTrainList()
    {
        $dateTime = $this->theDate;
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
        $url = "https://kyfw.12306.cn/otn/leftTicket/queryZ?leftTicketDTO.train_date=".$dateTime."&leftTicketDTO.from_station=".$from."&leftTicketDTO.to_station=".$to."&purpose_codes=ADULT";
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
        $curl->setOpt(CURLOPT_COOKIEJAR, $cookieSuccess);
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

    /**
        * 发送邮件给我
        *
        * @return array
     */
    public function sendEmail($title)
    {
        // 发送163邮件
        Mail::send('emails.msg',['data'=>$title],function($message){
            $userName = "抢票用户";
            $message->from('zzworkzz@163.com', $userName);
            $to = 'yanzongnet@163.com';
            $message->to($to)->subject("订票通知");
        });
        return true;
    }
}
