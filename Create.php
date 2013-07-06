<?php

/**
 * @name Action_Create
 * @desc create action, 和url对应
 * @author 秦磊(qinlei03@baidu.com)
 */
class Action_Create extends Ap_Action_Abstract {

    public function execute() {
        //1. check if user is login as needed
        $arrUserinfo = Saf_SmartMain::getUserInfo();
        $request_uri = $_SERVER['REQUEST_URI'];
        Header("Access-Control-Allow-Origin:*");

        if (strstr($request_uri, '/mediaservice') && empty($arrUserinfo)) {
            $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_NOT_LOGIN;
            $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_NOT_LOGIN;
            //echo json_encode($arrResult);

            if (!isset($arrInput['callback'])) {
                echo json_encode($arrResult);
            } else {
                echo $arrInput['callback'];
                echo '(';
                echo json_encode($arrResult);
                echo ')';
            }
            return;
        }


        //2. get and validate input params
        $arrRequest = Saf_SmartMain::getCgi();
        if ('POST' != $_SERVER['REQUEST_METHOD']) {
            $arrOutput['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
            $arrOutput['error_msg'] = Transcoding_Error::ERROR_MSG_NOT_USE_POST;
            //echo json_encode($arrOutput);

            if (!isset($arrInput['callback'])) {
                echo json_encode($arrOutput);
            } else {
                echo $arrInput['callback'];
                echo '(';
                echo json_encode($arrOutput);
                echo ')';
            }
            return;
        }
        $arrInput = $arrRequest['post']; //只运行post参数
        if (!empty($arrUserinfo['uid'])) {
            $arrInput['uid'] = $arrUserinfo['uid'];
        }
        if (empty($arrInput['uid']) && empty($arrInput['appid'])) {
            $arrOutput['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
            $arrOutput['error_msg'] = 'invalid user identify';
            //echo json_encode($arrOutput);

            if (!isset($arrInput['callback'])) {
                echo json_encode($arrOutput);
            } else {
                echo $arrInput['callback'];
                echo '(';
                echo json_encode($arrOutput);
                echo ')';
            }
            return;
        }

        if (empty($arrInput['uid'])) {
            $objServiceBcs = new Service_Page_BcsReq();
            $res = $objServiceBcs->get_uid_byappid($arrInput);
            if ($res['uid'] == NULL) {
                $arrOutput['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrOutput['error_msg'] = 'invalid user identify';
                //echo json_encode($arrOutput);
                if (!isset($arrInput['callback'])) {
                    echo json_encode($arrOutput);
                } else {
                    echo $arrInput['callback'];
                    echo '(';
                    echo json_encode($arrOutput);
                    echo ')';
                }
                return;
            }
            $arrInput['uid'] = $res['uid'];
        }
        //获取ak/sk
        $objServiceKey = new Service_Page_KeyManager();
        $ret = $objServiceKey->execute($arrInput);
        if (false == $ret) {
            $arrret['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
            $arrret['error_msg'] = 'invalid bcsinfo';
            //echo json_encode($arrret);
            if (!isset($arrInput['callback'])) {
                echo json_encode($arrret);
            } else {
                echo $arrInput['callback'];
                echo '(';
                echo json_encode($arrret);
                echo ')';
            }
            return;
        } else {
            $arrInput['bcsak'] = $ret['bcsak'];
            $arrInput['bcssk'] = $ret['bcssk'];
        }

        if (is_gbk($arrInput['input_name'])) {
            $arrInput['input_name'] = gbk_to_utf8($arrInput['input_name']);
        }
        if (is_gbk($arrInput['output_name'])) {
            $arrInput['output_name'] = gbk_to_utf8($arrInput['output_name']);
        }
        $objServicePageCreate = new Service_Page_Create();
        $arrPageInfo = $objServicePageCreate->execute($arrInput);

        //拼装输出结果
        //echo json_encode($arrPageInfo);
        if (!isset($arrInput['callback'])) {
            echo json_encode($arrPageInfo);
        } else {
            echo $arrInput['callback'];
            echo '(';
            echo json_encode($arrPageInfo);
            echo ')';
        }
    }

    private function check_is_chinese($s) {
        return preg_match('/[\x80-\xff]./', $s);
    }

}
