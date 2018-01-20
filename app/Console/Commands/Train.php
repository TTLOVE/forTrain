<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrainService;

class Train extends Command
{
    /**
     * 设置每天刷票时间
     */
    protected $timeSet = [
        //'begin' => " 07:00:00",
        'begin' => " 15:00:00",
        'end' => " 23:00:00"
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TrainGo {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'for my train';
    /**
     * 默认时间
     */
    private $theDate = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('开始');
        $TrainService = new TrainService($this->argument('date'));
        $this->step1($TrainService);
    }

    private function step1($TrainService)
    {
        $this->info('step1');
        $this->info('获取车次');
        $headers = ['车次', '开始', '结束', '票数'];
        $setFlag = true;
        $times = 1;
        // 不停的刷除非刷到
        while ($setFlag) {
            $data = [];
            $returnResult = $TrainService->searchTrainList();
            $this->info("第" . $times . "次");
            if ( $returnResult['status']==1 ) {
                $setFlag = false;
                foreach ($returnResult['data'] as $train) {
                    $data[] = [
                        $train['train'],
                        $train['begin'],
                        $train['end'],
                        $train['num'],
                    ];
                }
                $this->table($headers, $data);
            } else {
                if ( $returnResult['status']!=1 && empty($returnResult['data']) ) {
                    $this->error($returnResult['msg']);
                } else {
                    foreach ($returnResult['data'] as $train) {
                        $data[] = [
                            $train['train'],
                            $train['begin'],
                            $train['end'],
                            $train['num'],
                        ];
                    }
                    $this->table($headers, $data);
                }
                usleep(20000);
                $times++;
            }
        }
        $this->step2($TrainService);
    }

    private function step2($TrainService)
    {
        $this->info('step2');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('有并发送邮件通知');
        $TrainService->sendEmail("有票通知");
        $this->info('获取登录验证码图片');
        $checkChaFlag = true;
        $times = 1;
        while ($checkChaFlag) {
            $savePic = $TrainService->getLoginPic();
            $imgPath = "/usr/local/nginx/html/forTrain/" . $savePic;
            $this->info('获取登录验证码密码');
            $picResult = $TrainService->getPicMap($imgPath);
            if ( isset($picResult) && $picResult['ret']==0 ) {
                $this->info('第' . $times . '次校验验证码');
                $verify = str_replace("|", ",", $picResult['result']);
                $checkResult = $TrainService->checkCha($verify);
                if ( $checkResult['status']==1 ) {
                    $checkChaFlag = false;
                    $this->info("校验验证码成功");
                } else {
                    $TrainService->checkChaReportError($picResult['id']);
                    $this->error("校验验证码失败");
                }
            } else {
                $this->error("获取验证码密码失败");
            }
            $times++;
        }
        $this->step3($TrainService);
    }

    private function step3($TrainService)
    {
        $this->info('step3');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('登录用户');
        $userLoginResult = $TrainService->userLogin();
        if ( $userLoginResult['status']!=1 ) {
            $this->error('登录用户失败');
            $this->step2($TrainService);
            return false;
        }
        $this->step4($TrainService);
    }

    private function step4($TrainService)
    {
        $this->info('step4');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('登录首页');
        $TrainService->loginHome();
        $this->step5($TrainService);
    }

    private function step5($TrainService)
    {
        $this->info('step5');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('下单开始');
        $this->info('检查用户并获取:newapptk');
        $checkLoginData = $TrainService->checkUser();
        if ( $checkLoginData['status']!=1 ) {
            $this->error('获取newapptk失败');
            $this->step2($TrainService);
            return false;
        }
        $this->step6($TrainService, $checkLoginData['data']['newapptk']);
    }

    private function step6($TrainService, $newapptk)
    {
        $this->info('step6');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('验证这个客户端可以登录');
        $checkLoginData = $TrainService->checkUserLoginStatus($newapptk);
        if ( $checkLoginData['status']!=1 ) {
            $this->error('检验失败');
            $this->step2($TrainService);
            return false;
        }
        $this->step7($TrainService);
    }

    private function step7($TrainService)
    {
        $this->info('step7');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('检验用户信息');
        $checkLoginData = $TrainService->checkUserData();
        if ( $checkLoginData['status']!=1 ) {
            $this->error('检验用户信息失败');
            $this->step2($TrainService);
            return false;
        }
        $this->step8($TrainService);
    }

    private function step8($TrainService)
    {
        $this->info('step8');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('查看火车余票');
        $checkLoginData = $TrainService->checkTicket();
        if ( $checkLoginData['status']!=1 ) {
            $this->error('没有票');
            $this->step1($TrainService);
            return false;
        }
        $this->step9($TrainService, $checkLoginData['data']);
    }

    private function step9($TrainService, $trains)
    {
        $this->info('step9');
        $checkTimeResult = $this->timeCheck();
        if ($checkTimeResult['status']==0) {
            $this->error($checkTimeResult['msg']);
            $this->step1($TrainService);
            return false;
        }
        $this->info('添加订单');
        $checkLoginData = $TrainService->addOrder($trains);
        if ( $checkLoginData['status']!=1 ) {
            $this->error($checkLoginData['msg']);
            $this->step1($TrainService);
            return false;
        }
        $TrainService->sendEmail("请尽快支付");
        $this->info('添加订单成功,结束');
    }

    private function timeCheck()
    {
        $timeBegin = strtotime("Y-m-d " . $this->timeSet['begin']);
        $timeEnd = strtotime("Y-m-d " . $this->timeSet['end']);
        $nowTime = time();
        if ($nowTime>=$timeBegin && $nowTime<$timeEnd ) {
            $returnData = [
                'status' => 1
            ];
        } else {
            $returnData = [
                'status' => 0,
                'msg' => '时间不适合刷票,当前时间：' . date("Y-m-d H:i:s", $nowTime)
            ];
        }
        return $returnData;
    }
}
