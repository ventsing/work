<?php
/**
 * @file Create.php
 * @Brief   ps类，负责转码任务创建逻辑的输入参数校验、数据逻辑组织
 * @author 秦磊(qinlei03@baidu.com)
 * @version 1.0
 * @date 2013-04-23
 */
class Service_Page_Create
{
    private $objServiceDataTransJob;
    private $objServiceDataMetaData;
 //   private $objServiceDataBCSBucket;
    private $objServiceDataUserInfo;
    private $objServiceUas;
    //const BCS_DOMAIN = 'http://bcs-sandbox.baidu.com'; //线下沙盒环境 ,线上改为:http://bcs.duapp.com
    //static $BCS_DOMAIN  = '';
    //meta 信息提取常量
    const REQ_OFFLINE = '1'; //目前为离线转码，m3u8实时暂时不支持
    const REQ_ONLINE = '0';
    const RES_DATA_TYPE_BCS = '1';
    const REQ_DATA_NUM = '1'; //目前写死为1
    const HEADER_ACCEPT = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    const HEADER_ACCEPT_CHARSET = 'GBK,utf-8;q=0.7,*;q=0.3';
    const SOURCE_METHOD = 'GET';
    const META_FORMAT = '32768'; //获取meta的格式，目前写死
    const CALLBACK_METHOD = 'POST'; //支持PUT和POST，暂固定为POST
    const TRANSCODING_PRIORITY_OFFLINE = 1; 

    const RES_DATA_METHOD = 'PUT';


    //=============================format值转换====================================//
    //=========================00 0000 0000 000000================================//
    // 格式说明：15~14:MEDIA_TYPE, 13~10:CONTAINER, 9~6:AV_CODEC, 5~0:RESOLUTION //
    //MEDIA_TYPE
    const MEDIA_TYPE_VIDEO = 0x0000;        // 00 0000 0000 000000
    const MEDIA_TYPE_AUDIO = 0x4000;        // 01 0000 0000 000000
    //CONTAINER
    const CONTAINER_MOV = 0x0000;          // 00 0000 0000 000000
    const CONTAINER_MP4 = 0x0C00;           // 00 0011 0000 000000
    const CONTAINER_M4A = 0x0000;           // 00 0000 0000 000000
    const CONTAINER_MP3 = 0x0400;           // 00 0001 0000 000000
    const CONTAINER_M3U8 = 0x0400;          // 00 0001 0000 000000
    //AV_CODEC
    const AV_CODEC_H264_AAC_LC = 0x0000;    // 00 0000 0000 000000
    const AV_CODEC_MPEG4_AAC = 0x0040;      // 00 0000 0001 000000
    //RESOLUTION
    const RESOLUTION_176_144 = 0x0000;      // 00 0000 0000 000000
    const RESOLUTION_320_240 = 0x0001;      // 00 0000 0000 000001
    const RESOLUTION_480_224 = 0x0002;
    const RESOLUTION_480_320 = 0x0003;
    const RESOLUTION_480_360 = 0x0004;
    const RESOLUTION_640_360 = 0x0005;
    const RESOLUTION_640_480 = 0x0006;
    const RESOLUTION_854_480 = 0x0007;
    const RESOLUTION_960_540 = 0x0008;
    const RESOLUTION_960_720 = 0x0009;
    const RESOLUTION_1280_720 = 0x000A;
    const RESOLUTION_1280_960 = 0x000B;

    //output format 
    private static $arrOutputFormat = array('flv', 'mp4'); //写到配置文件

    //map
    private static $arrMediaType = array('video' => self::MEDIA_TYPE_VIDEO, 'audio' => self::MEDIA_TYPE_AUDIO,);
    private static $arrContainer = array(
        'mov' => self::CONTAINER_MOV, 
        'mp4' => self::CONTAINER_MP4, 
        'mp3' => self::CONTAINER_MP3, 
        'm4a' => self::CONTAINER_M4A, 
        'm3u8' => self::CONTAINER_M3U8,
    );
    private static $arrAVCodec = array('h264' => self::AV_CODEC_H264_AAC_LC, 'mpeg4' => self::AV_CODEC_MPEG4_AAC,);
    //private static $arrResolution = array(
        //'176_144' => self::RESOLUTION_176_144, 
        //'320_240' => self::RESOLUTION_320_240,
        //'480_224' => self::RESOLUTION_480_224,
        //'480_320' => self::RESOLUTION_480_320,
        //'480_360' => self::RESOLUTION_480_360,
        //'640_360' => self::RESOLUTION_640_360,
        //'640_480' => self::RESOLUTION_640_480,
        //'854_480' => self::RESOLUTION_854_480,
        //'960_540' => self::RESOLUTION_960_540,
        //'960_720' => self::RESOLUTION_960_720,
        //'1280_720' => self::RESOLUTION_1280_720,
        //'1280_960' => self::RESOLUTION_1280_960,
    //);
    private static $arrResolution = array(
        '25344' => self::RESOLUTION_176_144, 
        '76800' => self::RESOLUTION_320_240,
        '107520' => self::RESOLUTION_480_224,
        '153600' => self::RESOLUTION_480_320,
        '172800' => self::RESOLUTION_480_360,
        '230400' => self::RESOLUTION_640_360,
        '307200' => self::RESOLUTION_640_480,
        '409920' => self::RESOLUTION_854_480,
        '518400' => self::RESOLUTION_960_540,
        '691200' => self::RESOLUTION_960_720,
        '921600' => self::RESOLUTION_1280_720,
        '1228800' => self::RESOLUTION_1280_960,
    );

    private static $arrAdjustResolution = array( //按照从小到大排好序
        25344, 76800, 107520, 
        153600, 172800, 230400, 
        307200, 409920, 518400, 
        691200, 921600, 1228800,
    );

    private static $arrFormatToMediaType = array(
        //video type
        'mp4' => 'video', 
        'mov' => 'video',
        'flv' => 'video',
        'm3u8' => 'video',
        //audio type
        'mp3' => 'audio',
        'm4a' => 'audio',
    );

    private static $arrH264Profile = array('baseline', 'main', 'high',); 
    private static $arrMustParam = array( //必须参数
        'input_name' => 1, 
        'output' => 1, 
        'input_name' => 1, 
        'output_name' => 1, 
        'medianame' => 1, 
        'format' => 1,
    );
    private static $arrDefault = array( //非必须参数设置默认值
        'sniffer_source' => 0, 
        'input_bucket' => '', 
        'expiretime' => -1, 
        'skip_video' => 0, 
        'skip_audio' => 0,
        'width' => 640, 
        'height' => 480, 
        'aspect_ratio_mode' => 2, 
        'audio_sample_rate' => 44100, 
        'max_audio_sample_rate' => 44100, 
        'audio_channels' => 2,
    );

    private $arrErrMsg;

    private static $arrArgs = array(
        //系统参数，可以不需要filter，由平台保证其合法性,可采用配置文件的形式实现
        /*
	'scope' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array('self', 'filterScope'),
        ),*/ 
        'uid' => FILTER_VALIDATE_INT,
        'medianame' => FILTER_DEFAULT, //filter string
        'bdop_auth_level' => FILTER_VALIDATE_INT,
        'bdop_client_ip' => FILTER_VALIDATE_IP,
        'bdop_dev_user' => FILTER_VALIDATE_INT,
        //应用级参数，必须filter
       // 'input_bucket' =>   FILTER_SANITIZE_STRING,
        'input_name' =>   FILTER_SANITIZE_STRING,
       // 'output_bucket' =>   FILTER_SANITIZE_STRING,
        'output_name' =>   FILTER_SANITIZE_STRING,
        'sniffer_source' => array(
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0, 'max_range' => 1), // 'default' => 0),
        ),
        'expiretime' => array(
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => -1), // 'default' => -1), //默认永久存活:-1
        ),
        'output' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array('self', 'filterOutput'),//json格式，回调函数filterOutput验证是否是合法的json
        ), 
    );

    private static $arrOutputArgs = array(
   //     'res_url' => array(
     //       'filter' => FILTER_CALLBACK,
     //       'options' => array('self', 'filterResUrl'),
     //   ), //FILTER_VALIDATE_URL,
        //'format' =>  FILTER_DEFAULT,        
        'format' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array('self', 'filterFormat'),
        ),
        'skip_video' => array(
            'filter' => FILTER_VALIDATE_INT,
            //'flags' => FILTER_NULL_ON_FAILURE,
            'options' => array('min_range' => 0, 'max_range' => 1), //'default' => 0),
        ),
        'skip_audio' => array(
            'filter' => FILTER_VALIDATE_INT,
            //'flags' => FILTER_NULL_ON_FAILURE,
            'options' => array('min_range' => 0, 'max_range' => 1),
        ),
        'width'  => array( //加上限
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0), //'default' => 640),
        ),
        'height' => array( //加上限
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0),// 'default' => 480),
        ),
        'aspect_ratio_mode' => array( //期望的比例方案。0：原始宽高比缩放，尽量适配到用户指定的宽高；1：加黑边强制到用户指定宽高；2：画面平铺到用户指定宽高
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0, 'max_range' => 2),// 'default' => 0), //默认为0
	),
	'video_bitrate_kbps' => array(
			'filter' => FILTER_VALIDATE_INT,
			'options' => array('min_range' => 0), //'default' => 44100),
	),
	'audio_bitrate_kbps' => array(
			'filter' => FILTER_VALIDATE_INT,
			'options' => array('min_range' => 0), //'default' => 44100),
	),

        'frame_rate' => FILTER_VALIDATE_FLOAT, //默认为输入帧率 ?默认输入帧率用0表示？
        'max_frame_rate' => FILTER_VALIDATE_FLOAT, //限制最大的输出帧率
        'audio_sample_rate' => array(
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0), //'default' => 44100),
        ),
        'max_audio_sample_rate' => array(
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 0), //'default' => 44100),
        ),
        'audio_channels' => array(
            'filter' => FILTER_VALIDATE_INT,
            'options' => array('min_range' => 1, 'max_range' => 2), //'default' => 2),
        ),
        'h264_profile' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array('self', 'filterH264Profile'), //检测h264_profile是否是main|high|baseline其中之一
        ), 
        'callback' => array(
            'filter' => FILTER_CALLBACK,
            'options' => array('self', 'filterOutCallback'), //检测h264_profile是否是main|high|baseline其中之一
        ), 
    );



    public function __construct()
    {
        $this->objServiceDataTransJob = new Service_Data_TransJob();
        $this->objServiceDataMetaData = new Service_Data_MetaData();
    //    $this->objServiceDataBCSBucket = new Service_Data_BCSBucket();
        $this->objServiceDataUserInfo = new Service_Data_UserInfo();
         $this->objServiceUas = new Service_Data_Uas();
		//self::$BCS_DOMAIN = Bd_Conf::getAppConf('/module/bcs/hostname');
    }

    public function execute($arrInput)
    {
        Bd_Log::debug('create page service called');
        try
        {
            //$strPost = json_encode($arrInput); //直接在$arrInput里带入post,无需json_encode
            $uid = $arrInput['uid'];
            $name = $arrInput['medianame'];
            $bcsak = $arrInput['bcsak'];
            $bcssk = $arrInput['bcssk'];
            $arrResult = array();
            $arrInputFilter = filter_var_array($arrInput, self::$arrArgs); //检查所有参数，如果有多个错误，则返回多个出错的参数信息

            //针对上面filter的结果，NULL则判断该参数是否是必须的，如果必须则返回错误；FALSE则说明filter检验不合法，直接返回错误
            $bFlag = true;
           // echo json_encode($arrInputFilter);
            $arrJudgeRes = $this->judgeFilterResult($arrInputFilter, $bFlag);
            if (!$bFlag)//出错，返回错误
            {
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = $this->arrErrMsg;
                return $arrResult;
            }
	    $pattern='/[~!@#$&*()]/';
	    $ti0 = preg_match($pattern, $arrInput['input_name'], $matches, PREG_OFFSET_CAPTURE);
	    $ti1 = preg_match($pattern, $arrInput['output_name'], $matches, PREG_OFFSET_CAPTURE);
	    if(1==$ti0||1==$ti1){
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = 'invalid input or output name';
                return $arrResult;
	    }
          //  echo  json_encode($arrJudgeRes);
            $arrQuataFilds=array(
                'uid',
		'medianame',
		'inbucket',
		'outbucket',
                'used_quota',
                'total_quota',
                'expiretime',
                'valid',
            );
            $arrConds=array(
                'uid='=>$uid,
		'medianame='=>$name,
            );
            $res = $this->objServiceDataUserInfo->getInfo($arrQuataFilds,$arrConds);
            //echo json_encode($res);
	    if(false === $res){
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_INVALID_QUOTA;
                return $arrResult;
            }
            if(intval($res[0]['valid'])!=1){
		$arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_INVALID_QUOTA;
                return $arrResult;
            }
            if(intval($res[0]['expiretime']) > 0 && intval($res[0]['expiretime'])< time()){
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_INVALID_QUOTA;
                return $arrResult;
            }
            if(intval($ret[0]['used_quota'])>intval($res[0]['total_quota'])){
		$arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INVALID_PARAMETER;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_INVALID_QUOTA;
                return $arrResult;
            }
            $arrInputRes = $arrJudgeRes;
            $strPost = json_encode($arrInputRes);
            $output_name = $arrInputRes['output_name'];
            $input_name = $arrInputRes['input_name'];
            $bucket_in = $res[0]['inbucket'];
            $bucket_out = $res[0]['outbucket'];
            if(NULL==$bucket_out || NULL==$bucket_in){
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INTERNAL_PARAMETER;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_INTERNAL_PARAMETER;
                return $arrResult;
            }

            $arrInsert = array(
                'src_file_id' => 0,
                'dst_file_id' => 0,
                'uid' => $arrInputRes['uid'],
                'medianame' => $arrInputRes['medianame'],
              //  'output_format' => $arrInputRes['output']['format'],
                'video_source_bucket' => $bucket_in,
                'video_source_name' => $arrInputRes['input_name'],
                'sniffer_source' => ($arrInputRes['sniffer_source'] == 0 ? 'N' : 'Y'),
                'expiretime' => $arrInputRes['expiretime'],
                'job_state' => Transcoding_JobState::JOB_WAIT,
                'trans_info' => $strPost,
                'create_time' => time(),
                'finish_time' => 0,
                'video_dst_bucket' => $bucket_out,
                'video_dst_name' => $arrInputRes['output_name'],
                'priority' => 0,
            );
	    $bcsDomain = Bd_Conf::getAppConf('/module/bcs/hostname');
            $src_media_url = $this->getResGetUrl($uid, $input_name, $bcsak,$bcssk, $bucket_in, $bcsDomain); 
            $s_info = $this->getFileInfo($src_media_url);           
            $s_md5=$s_info['md5'];
	    if($s_info['code']!=200){
		$arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INTERNAL_PARAMETER;
                $arrResult['error_msg'] = 'invalid source file';
                return $arrResult;
	    }
            //echo json_encode($s_info);
            $dst_get_url = $this->getResGetUrl($uid, $output_name, $bcsak,$bcssk, $bucket_out, $bcsDomain);
            $dst_info = $this->getFileInfo($dst_get_url);           
            if($dst_info['code']==200){
		$arrResult['error_code'] = Transcoding_Error::ERROR_CODE_INTERNAL_PARAMETER;
                $arrResult['error_msg'] = 'output file existed';
                return $arrResult;
	    }
	  //  return $arrInsert;
//           // echo json_encode($arrInsert);
            //return $arrInsert; 
            //$objServiceDataCreate= new Service_Data_Create();
            $jobid = $this->objServiceDataTransJob->addTransJob($arrInsert); //job写入数据库
            Bd_Log::debug('addtransjob' . var_export($jobid, true));
            if (false == $jobid)
            {
                Bd_Log::fatal("add transjob fail");
                return;
            }

do{
	    $type = 1; //源文件回调
            $bvs = Bd_Conf::getAppConf('/module/bvs');
            $bvsIP = $bvs['ip'];
            $bvsPort = $bvs['port'];
            $callbackDomain = $bvsIP . ':' . $bvsPort;
            $callbackurl = 'http://'."$callbackDomain/transcoding/metainfo/getmeta?jobid=$jobid&medianame=$medianame&uid=$uid&md5=$s_md5&type=$type"; //将CALLBACK_IP写入conf文件

            $res_data_url = '';
            $res_data_url = $this->getResPutUrl($uid, $output_name, $bcsak,$bcssk, $bucket_out, $bcsDomain, Transcoding_ConstParam::THUMBNAIL_FORMAT); //thumbnail
	    $arrMeta = $this->objServiceDataMetaData->getMetaByRal($jobid, $src_media_url, $callbackurl, $res_data_url); //使用回调,完成回调之后，写入数据库
           // echo 'src_media_url:'.$src_media_url.' callbackurl:'.$callbackurl.' puturl:'.$res_data_url;
	    if (false === $arrMeta) //必须严格跟false做比较，后端数据可能返回为空 
            {
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_META;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_META;
                Bd_Log::warning("get meta error!");
                return $arrResult;
            }
            }while(false);
            //return;
            $mediaUrl = $src_media_url;
            //$mediaUrl = $arrInputRes['url'];
            $format = $arrInputRes['output']['format'];
            //$bucket = 'test-qinlei'; //需要从数据库中获取
            $resUrl = '';
            $resUrl = $this->getResPutUrl($uid, $output_name, $bcsak,$bcssk, $bucket_out, $bcsDomain);
            $res_get_url = $this->getResGetUrl($uid, $output_name, $bcsak,$bcssk, $bucket_out, $bcsDomain);

            $arrTrans = array();
            $arrTrans['url'] = $mediaUrl;
            $arrTrans['uid'] = $uid;
            $arrTrans['res_data_url'] = $resUrl;
            $arrTrans['res_data_head_url'] = $res_get_url;
	    $arrTrans['res_get_url'] = $res_get_url;
            $arrTrans['appid'] = $uid;
            $arrTrans['container_format'] = $format;//$arrInputRes['output']['format'];
            $arrTrans['width'] = (empty($arrJudgeRes['output']['width'])?"640":strval($arrJudgeRes['output']['width']));
            $arrTrans['height'] = (empty($arrJudgeRes['output']['height'])?"0":strval($arrJudgeRes['output']['height']));
            $arrTrans['skip_video'] = (empty($arrJudgeRes['output']['skip_video'])?"0":strval($arrJudgeRes['output']['skip_video']));
            $arrTrans['skip_audio'] = (empty($arrJudgeRes['output']['skip_audio'])?"0":strval($arrJudgeRes['output']['skip_audio']));
            $arrTrans['aspect_ratio_mode'] = (empty($arrJudgeRes['output']['aspect_ratio_mode']) ? "0":strval($arrJudgeRes['output']['aspect_ratio_mode']));
            $arrTrans['video_bitrate'] = (empty($arrJudgeRes['output']['video_bitrate_kbps'])?"600000":strval($arrJudgeRes['output']['video_bitrate_kbps']));
            $arrTrans['audio_bitrate'] = (empty($arrJudgeRes['output']['audio_bitrate_kbps'])?"128000":strval($arrJudgeRes['output']['audio_bitrate_kbps']));
            $arrTrans['max_frame_rate'] = (empty($arrJudgeRes['output']['max_frame_rate'])?"15":strval($arrJudgeRes['output']['max_frame_rate']));
            $arrTrans['frame_rate'] = (empty($arrJudgeRes['output']['frame_rate'])?"15":strval($arrJudgeRes['output']['frame_rate']));
            $arrTrans['sample_rate'] = (empty($arrJudgeRes['output']['audio_sample_rate'])?"44100":strval($arrJudgeRes['output']['audio_sample_rate']));
            $arrTrans['channels'] = (empty($arrJudgeRes['output']['channels'])?"2":strval($arrJudgeRes['output']['channels']));
            $arrTrans['h264_profile'] = (empty($arrJudgeRes['output']['h264_profile'])?"main":strval($arrJudgeRes['output']['h264_profile']));
            $arrTrans['ucallback'] = $arrJudgeRes['output']['callback'];
            $resTranscoding = false;
	    $resTranscoding = $this->requestTranscoding($callbackDomain, $jobid, $uid, $arrTrans); //离线转码
            if (false === $resTranscoding)
            {                
                $arrResult['error_code'] = Transcoding_Error::ERROR_CODE_TRANSCODING;
                $arrResult['error_msg'] = Transcoding_Error::ERROR_MSG_TRANSCODING;
                Bd_Log::warning("transcoding create fail!");
                return $arrResult;
            }
	                //�޸�transjob��,�޸�job_state��thumbnail_url��video_res_url��dst_file_idΪ0
	    $arrFields = array(
			    'job_state' => Transcoding_JobState::JOB_TRANSCODING,
			    );
	    $arrConds = array(
			    'jobid=' => intval($jobid),
			    'uid=' => intval($uid));
	    $updateRes = $this->objServiceDataTransJob->updateTransJobByConds($arrFields, $arrConds);
	    if (false === $updateRes)
	    {
		    $arrResult = array(
				    'error_code' => Transcoding_Error::ERROR_CODE_INTERNAL_PARAMETER,
				    'error_msg' => Transcoding_Error::ERROR_MSG_INTERNAL_PARAMETER,
				    );
		    Bd_Log::debug("createjob:" . var_export($arrResult, true));
		    return $arrResult;
	    }
        }catch(Exception $e)
            {
                //Bd_Log::warning($e->getMessage(), $e->getCode());
                $arrResult['error_code'] = $e->getCode();
                $arrResult['error_msg'] = $e->getMessage();
                return $arrResult;
            }

        $arrResult['jobid'] = $jobid; //成功返回jobid
        Bd_Log::debug("create job sucess!jobid :$jobid");
        return $arrResult;
    }


    /**
     * @Brief filter the scope param
     *
     * @Param $scope
     *
     * @Returns  result of filter 
     */
    private static function filterScope($scope)
    {
        $arrScope = explode(',', $scope);
        return filter_var($arrScope, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);
    }

    /**
     * @Brief filter the output format, only can be flv\mp4\m3u8
     *
     * @Param$format
     *
     * @Returns
     */
    private static function filterFormat($format)
    {
        if (false === array_search($format, self::$arrOutputFormat)) //不支持的format
        {
            return false;
        }
        return $format;
    }

    /**
     * @Brief filter the output param
     *
     * @Param $output
     *
     * @Returns   
     */
    private static function filterOutput($output) //需要urldecode
    {
        $arrOutput = json_decode($output, true);
        if (NULL === $arrOutput)
        {
            return false; //不是合法的json格式
        }
        //if (JSON_ERROR_NONE == json_last_error()) // need php version higher than 5.3
        //{
        //    return  $arrOutput;
        //}

        //检验json串里的参数是否合法
        return filter_var_array($arrOutput, self::$arrOutputArgs);
    }

    /**
     * @Brief 检查URL是否合法，并对URL urlencode处理
     *
     * @Param $url
     *
     * @Returns   
     */
    private static function filterURL($url)
    {
        $validateURL = filter_var($url, FILTER_VALIDATE_URL);//, FILTER_FLAG_PATH_REQUIRED);
        if (!$validateURL)
        {
            Bd_Log::debug('filterurl validate false!');
            return FALSE; 
        }

        $encodeURL = filter_var($url, FILTER_SANITIZE_ENCODED);//如果未urlencode则将其urlencode
        //Bd_Log::debug("----33333333333333333333--------------------------------". var_export($encodeURL, true) ."+++++++++++++++++++-------------request out  post");
        if (!$encodeURL)
        {
            return FALSE;
        }

        return $encodeURL;
    }


    /**
     * @Brief 检查H264Profile是否合法
     *
     * @Param $h264_profile
     *
     * @Returns   
     */
    private static function filterOutCallback($callback)
    {
        return $callback;
    }

    
    private static function filterH264Profile($h264_profile)
    {
        if (FALSE === array_search($h264_profile, self::$arrH264Profile))
        {
            return FALSE;
        }
        return $h264_profile;
    }

    /**
     * @Brief 判断filter过程中是否有错误
     *
     * @Param $arrFilterRes
     *
     * @Returns   
     */
    private function judgeFilterResult($arrFilterRes, &$bErrFlag)
    {
        //$bErrFlag = TRUE;
        $arrJudgeRes = array();
        foreach ($arrFilterRes as $key => $value)  //递归处理
        {
            if (is_array($value))
            {
                $arrJudgeRes = $this->judgeFilterResult($value, $bErrFlag);
            }
            elseif (NULL === $value || empty($value)) //用户没有传递该参数，判断是否是必须参数
            {
                if (1 == self::$arrMustParam[$key]) //必须参数,返回错误
                {
                    $this->arrErrMsg[] = $key . " invalid"; //目前写死，可以考虑使用配置文件的方式，便于修改错误信息描述，而不改动代码
                    $bErrFlag = FALSE;
                }
                else //非必须参数，设置默认值 ，必须要在所有参数都没有出淼那榭鱿虏判枰柚媚现?                
                {
                    $arrFilterRes[$key] = $arrDefault[$key];
                }
            }
            elseif (FALSE === $value)
            {
                $this->arrErrMsg[] = $key . " error value!";
                $bErrFlag = FALSE;
            }
        }
        //$arrJudgeRes['flag'] = $bErrFlag;
        //$arrJudgeRes['filterRes'] = $arrFilterRes;
        //return $arrJudgeRes;
        return $arrFilterRes;
    }

    private function requestTranscoding($callbackDomain, $jobid, $uid, $arrInput)
    {
	//echo 'videoasyn requestTranscoding';
        $ucl = urlencode($arrInput['ucallback']);
        $res_get_url = urlencode($arrInput['res_get_url']);
        $callbackurl = $callbackDomain . "/transcoding/job/finish?jobid=$jobid&uid=$uid&geturl=$res_get_url&callbackurl=$ucl"; //告知是哪个job转码完成
        //$output_format = '3076';
       // $output_format = $this->getTransFormat($arrInput['format'], $arrInput['width'], $arrInput['height']);
        $uid = $arrInput['uid'];
        $arrPostFields = array(
            'req_offline' => self::REQ_OFFLINE,
            'authorid' => '', //用户标识号，暂时保留
            'authorkey' => '', //用户请求准入认证，暂时保留
            'res_data_type' => self::RES_DATA_TYPE_BCS,// 结果直接放回:0, bcs:1, 直接放回且存入bcs:2 （默认为1）
            'req_data_num' => self::REQ_DATA_NUM,//固定为1
            'req_data_source' => array(
                array(
                    'http_reqpack' => array(
                        'Accept' => self::HEADER_ACCEPT,
                        'Accept-Charset' => self::HEADER_ACCEPT_CHARSET,
                    ),
                    'source_url' => $arrInput['url'],
                    'sourcemethod' => self::SOURCE_METHOD,
                    'operations' => array(
                        'reqdid' =>  "$jobid", //md5($arrInput['url']),
                        'output_format' => "3075",//"$output_format",
                        'start_time_ms' => '0',
                        'priority' => self::TRANSCODING_PRIORITY_OFFLINE, 
                        'res_data_method' => self::RES_DATA_METHOD,
                        'res_data_head_url' => $arrInput['res_data_head_url'],
                        'sync_period' => '0',
                        'appid' => "$uid",
                        'jobid' => "$jobid",
			'container_format'=>$arrInput['container_format'],
			'width'=>$arrInput['width'],
			'height'=>$arrInput['height'],
			'height'=>$arrInput['height'],
			'skip_video'=>$arrInput['skip_video'],
			'skip_audio'=>$arrInput['skip_audio'],
			'aspect_ratio_mode'=>$arrInput['aspect_ratio_mode'],
			'video_bitrate'=>$arrInput['video_bitrate'],
			'audio_bitrate'=>$arrInput['audio_bitrate'],
			'max_frame_rate'=>$arrInput['max_frame_rate'],
			'frame_rate'=>$arrInput['frame_rate'],
			'sample_rate'=>$arrInput['sample_rate'],
			'channels'=>$arrInput['channels'],
			'h264_profile'=>$arrInput['h264_profile'],
                    ),
                    'callbackurl' => $callbackurl,
                    'callbackmethod' => self::CALLBACK_METHOD,
                    'reqdid' => "$jobid",
                    'res_data_url' => $arrInput['res_data_url'],
                ),
            ),
        );


        $strPost = json_encode($arrPostFields);
       // echo $strPost;
	//return true;
        Bd_Log::warning('requestranscoding:' . var_export($arrPostFields, true));
        ral_set_pathinfo('/videoasyn');
        //ral_set_logid($jobid);
        ral_set_header('Tc-Json-Private-Method:true');
	$ret = ral('videoasyn', 'post', $strPost, rand());
        //ral_set_pathinfo("/transcoding/metainfo/getmeta");
        //$ret = ral('demo', 'post', $arrPostFields, 1231);
        if($ret === false){
            echo 'errno:'.ral_get_errno()."\n";
            echo 'error_msg:'.ral_get_error()."\n";
            echo 'protocol_status:'.ral_get_protocol_code()."\n";
        }else{
            echo $ret;
        }
        return $ret;
    }

    /**
     * @Brief   请求后端离线转码服务
     *
     * @Param   $jobid
     * @Param   $arrInput
     *
     * @Returns  false:转码失败 true:成功提交转码服务，不代表转码成功，转码结果通过回调通知 
     */
     private function requestFileInfoByCurl($arrInput){
       $url = $arrInput['url'];
       $method = $arrInput['method'];
	$options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_VERBOSE => TRUE,
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
	$arrRes = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	curl_close($ch);
        $returl=array(
		'httpcode'=>$code,
		'content'=>$arrRes,
	);
        return $returl;
	}

    /**
     * @Brief 根据formt 获取format编码值
     *
     * @Param$format
     * @Param$width
     * @Param$height
     *
     * @Returns
     */
    private function getTransFormat($format, $width, $height)
    {        
        $mediaType = self::$arrFormatToMediaType[$format];
        Bd_Log::warning('getransformatmediatype:' . var_export($mediaType, true));
        //$strResolution = $width . '_' . $height;
        $strResolution = $width * $height;
        Bd_Log::warning('getransformatresolution:' . var_export($strResolution, true));
        $container = $format;
        Bd_Log::warning('getransformatcontainer:' . var_export($container, true));
        $avCodec = 'h264'; //目前默认写死
        if (!isset(self::$arrResolution[$strResolution]))
        {
            $strResolution = $this->adjustResolution($width, $height);
            Bd_Log::warning('adjustresolution111:' . var_export($strResolution, true));
        }
        $output_format = self::$arrMediaType[$mediaType] | self::$arrContainer[$container] | self::$arrAVCodec[$avCodec] | self::$arrResolution["$strResolution"];
        return $output_format;
    }

    private function getResGetUrl($uid, $name,$ak,$sk, $bucket, $domain)
    {
        //$bcsConf = Bd_Conf::getAppConf('/module/bcs');
        //$ak = $bcsConf['ak'];
        //$sk = $bcsConf['sk'];
        //Bd_Log::debug("getresurl:ak:$ak, sk:$sk, name:$name, buckt:$bucket");
        $flag = 'MBO';
        $method = 'GET';
        $object = '';
        $object = $name;
        $sign = $this->getSign($ak, $sk, $flag, $method, $bucket, $object); //ak\sk放在数据库中
        $resUrl = 'http://'.$domain . '/' . $bucket . '/' . $object . '?sign=' . $sign;
        return $resUrl;
    }

    private function getFileInfo($file){
       $ret=array(
            'code'=>404,
            'md5'=>NULL,
	     );
       $arrin=array(
		'url'=>$file,
		'method'=>'HEAD',
	);
	$resinfo=$this->requestFileInfoByCurl($arrin);
        $ret['code'] = intval($resinfo['httpcode']);
	$strcont = $resinfo['content'];
	if(NULL!=$strcont){
	   $cmd5 = strstr($strcont,'Content-MD5:');
	   $strarr = explode("\r\n", $cmd5);
	   $md5str = substr($strarr[0],12);
	   $ret['md5']= trim($md5str); 
	}else{
	   $ret['md5']=md5($file);
        }
       return $ret;
    }

    private function getResPutUrl($uid, $name, $ak, $sk, $bucket, $domain, $thumbnailFormat = NULL)
    {
       // $bcsConf = Bd_Conf::getAppConf('/module/bcs');
      //  $ak = $bcsConf['ak'];
       // $sk = $bcsConf['sk'];
        Bd_Log::debug("getresurl:ak:$ak, sk:$sk, uid:$uid, thumbnail:$thumbnailFormat, $format, $bucket");
        $flag = 'MBO';
        $method = 'PUT';
        $object = '';
        if (NULL == $thumbnailFormat)
        {
            $object = $name;
        }
        else 
        {
            $object = $name . '.' . $thumbnailFormat;
        }
        Bd_Log::debug("****************************$object");
        $sign = $this->getSign($ak, $sk, $flag, $method, $bucket, $object); //ak\sk放在数据库中
        $resUrl = 'http://'.$domain . '/' . $bucket . '/' . $object . '?sign=' . $sign;
        return $resUrl;
    }

    private function getSign($ak, $sk, $flag, $method, $bucket, $object, $time = NULL, $ip = NULL, $size = NULL)
    {
        $content =  $flag . "\n"
            . "Method=$method" . "\n"
            . "Bucket=$bucket"  . "\n"
            . "Object=/$object" . "\n";
        if (NULL != $time)
        {
            $content .= "Time=$time" . "\n";
        }
        if (NULL != $ip)
        {
            $content .= "Ip=$ip" . "\n";
        }
        if (NULL != $size)
        {
            $content .= "Size=$size" . "\n";
        }
        //$ak='i0DrkCiANrjbvKXRaB';
        //$SecretKey='XrAp0ZjexakC0jFqJIPJW8sxs4KATMl';
        $signture=urlencode(base64_encode(hash_hmac('sha1', $content, $sk,true)));
        $sign = $flag . ":" . "$ak" . ":" . "$signture";
        //$url = "$value: " . $sandbox . "$fileId.mov?sign=$sign\n";
        return $sign;

    }

    private function filterResUrl($strResUrl) //探测url是否可以put
    {
        $http = curl_init($strResUrl);
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($http);

        $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
        curl_close($http);
        if (200 != intval($http_status))
        {
            return false;
        }
        return $strResUrl;
    }

    private function adjustResolution($width, $height)
    {
        $intResolution = $width * $height; 
        $subtraction = 0;
        $intLeft = $intResolution;
        $intRight = $intResolution;
        foreach (self::$arrAdjustResolution as $resolution)
        {
            $sub = $resolution - $intResolution;
            if ($sub < 0)
            {
                $intLeft = $resolution;
            }
            else
            {
                $intRight = $resolution;
                break;
            }
        }
        $intRightSub = $intRight - $intResolution;
        $intLeftSub = $intResolution - $intLeft;
        if ($intRightSub > $intLeftSub && $intLeftSub != 0)
        {
           return $intLeft; 
        }
        return $intRight;
    }

}
