<?php
/**
 * 生成随机字符串
 * User: 木
 * Date: 2018/7/30 16:52
 * @param int $length
 * @return string
 */
function NoticeStr ($length = 6) {
    $NoticeStr = '';
    $str = 'qwertyuiopasdfghjklzxcvbnm0123456789QWERTYUIOPASDFGHJKLZXCVBNM';
    for ( $i = 1; $i <= $length; $i++ ) {
        $RandNum = mt_rand (0 , strlen ($str) - 1);
        $NoticeStr .= $str[$RandNum];
    }
    return $NoticeStr;
}

/**
 * 生成密码
 * 说明：$salt为空则生成一个随机字符串，并返回一个数组
 * User: 木
 * Date: 2018/7/30 16:52
 * @param string $initValue
 * @param string $salt
 * @return array|string
 */
function CreatePassword ($initValue = '' , $salt = '') {
    // 生成密码还是验证密码
    $R = empty($salt) ? true : false;
    $salt = empty($salt) ? NoticeStr (6) : $salt;
    $pwd = md5 (sha1 (md5 ($initValue) . md5 ($salt)));
    return $R ? array ('password' => $pwd , 'salt' => $salt) : $pwd;
}

/**
 * 获取密码
 * @param string $password 准备加密的密码
 * @return array
 * User: jinrui.wang wangjinrui2010@163.com
 * Date:2018/05/17 上午11:20
 */
function getPassword ($password) {
    $salt = substr (md5 (date ('ymd')) , 0 , 6);
    $password = md5 (md5 ($password . $salt));
    return array ('password' => $password , 'salt' => $salt);
}

/**
 * 验证密码
 * @param string $password 验证的密码
 * @param int $id 用户ID
 * @return boolean
 * User: zjj
 * Date:2018/08/17
 */
function checkPassword ($password , $salt , $old_password) {

    $a = CreatePassword ($password , $salt) !== $old_password;
    if ( CreatePassword ($password , $salt) !== $old_password ) {
        return 1;
    } else {
        return 2;
    }
}

/**
 * 无限级分类 - 递归
 * Date: 2018/8/6 11:16
 * @param array $list
 * @param string $pk 主键名
 * @param string $p_key 上级键名
 * @param int $p_val 一级列表上级键值
 * @param int $level
 * @return array
 */
function sunTree ($list , $pk = 'id' , $p_key = 'p_id' , $p_val = 0 , $level = 1) {
    $data = array ();
    foreach ( $list as $key => $item ) {
        if ( $item[$p_key] == $p_val ) {
            $temp = $item;
            unset($list[$key]);
            $temp['level'] = $level;
            $temp['sub_list'] = sunTree ($list , $pk , $p_key , $temp[$pk] , $level + 1);
            $data[] = $temp;
        }
    }
    return $data;
}

/**
 * 导出数据为excel表格
 *param $data    一个二维数组,结构如同从数据库查出来的数组
 *param $title   excel的第一行标题,一个数组,如果为空则没有标题
 *param $filename 下载的文件名
 *examlpe
 * $stu = M ('User');
 *$arr = $stu -> select();
 *exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
 */
function exportexcel ($data = array () , $title = array () , $filename = 'report') {
    header ("Content-type:application/octet-stream");
    header ("Accept-Ranges:bytes");
    header ("Content-type:application/vnd.ms-excel");
    header ("Content-Disposition:attachment;filename=" . $filename . ".xls");
    header ("Pragma: no-cache");
    header ("Expires: 0");
    //导出xls 开始
    if ( !empty($title) ) {
        foreach ( $title as $k => $v ) {
            $title[$k] = iconv ("UTF-8" , "GB2312" , $v);
        }
        $title = implode ("\t" , $title);
        echo "$title\n";
    }
    if ( !empty($data) ) {
        foreach ( $data as $key => $val ) {
            foreach ( $val as $ck => $cv ) {
                $data[$key][$ck] = iconv ("UTF-8" , "GBK//ignore" , $cv);
            }
            $data[$key] = implode ("\t" , $data[$key]);
        }
        echo implode ("\n" , $data);
    }
}

/**
 * 验证身份证号
 * User: 木
 * Date: 2018/8/15 9:32
 * @param $id
 * @return bool
 */
function isIdcard ($id) {
    $id = strtoupper ($id);
    $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array ();
    if ( !preg_match ($regx , $id) ) {
        return false;
    }
    // 检查15位
    if ( 15 == strlen ($id) ) {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
        @preg_match ($regx , $id , $arr_split);
        // 检查生日日期是否正确
        $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if ( !strtotime ($dtm_birth) ) {
            return false;
        } else {
            return true;
        }
    } else { // 检查18位
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match ($regx , $id , $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if ( !strtotime ($dtm_birth) ) { // 检查生日日期是否正确
            return false;
        } else {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array (7 , 9 , 10 , 5 , 8 , 4 , 2 , 1 , 6 , 3 , 7 , 9 , 10 , 5 , 8 , 4 , 2);
            $arr_ch = array ('1' , '0' , 'X' , '9' , '8' , '7' , '6' , '5' , '4' , '3' , '2');
            $sign = 0;
            for ( $i = 0; $i < 17; $i++ ) {
                $b = (int)$id{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ( $val_num != substr ($id , 17 , 1) ) {
                return false;
            } else {
                return true;
            }
        }
    }
}

/**
 * 方法释义
 * @param $id 所查图片ID
 * @param string $type 图片类型  默认缩略图
 * @return string
 * User: jinrui.wang wangjinrui2010@163.com
 * Date: 2018/8/7
 */
function getPic ($id , $type = '') {
    if ( $id == '' || $id == 0 ) {
        return '';
    }
    if ( $type == 'th' ) {
        $field = 'th_savepath';
    } else {
        $field = 'path';
    }
    return C ('API_URL') . D ('File')->where (array ('id' => $id))->getField ($field);
}

/**
 * 图片上传
 * @param file $file 文件
 * @param string $path 子目录 可空
 * @return array
 * User: yihui.qu 849097526@qq.com
 * Date:2018/05/22 上午11:26
 */
function uploadimg ($file , $path = '') {
    if ( !empty($file) ) {
        $root = "./Uploads/";
        $path = $path . "/";
        $new_file = $root . $path;
        if ( !file_exists ($new_file) ) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir ($new_file , 0700);
        }
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array ('jpg' , 'gif' , 'png' , 'jpeg');// 设置附件上传类型
        $upload->rootPath = $root; // 设置附件上传根目录
        $upload->savePath = $path; // 设置附件上传（子）目录 // 上传文件
        $upload->saveName = array ('uniqid' , '');
        $info = $upload->upload ();
        if ( !$info ) {// 上传错误提示错误信息
            return $upload->getError ();
        } else {// 上传成功
            foreach ( $info as $file ) {
                $savepath = $root . $file['savepath'] . $file['savename'];//原图
                $th_savepath = $root . $file['savepath'] . "th_" . $file['savename'];//缩略图
                $size = $file['size'];//文件大小
                $name = $file['name'];//文件原名
                $ext = $file['ext'];//文件类型
            }
        }
        if ( !empty($savepath) ) {//上传文件名不为空
            $image = new \Think\Image();
            $image->open ($savepath);
            // 按照原图的比例生成一个最大为150*150的缩略图并保存
            $image->thumb (150 , 150)->save ($th_savepath);//thumb(图片宽，图片高)
            $add['savepath'] = substr ($savepath , 1);
            $add['th_savepath'] = substr ($th_savepath , 1);
            $add['path'] = substr ($savepath , 1);
            $add['abs_url'] = $_SERVER['HTTP_HOST'] . substr ($savepath , 1);
            $add['size'] = $size;
            $add['name'] = $name;
            $add['ext'] = $ext;
            $add['create_time'] = time ();
            $save = D ('file')->add ($add);
            $info['savepath'] = $savepath;
            $info['th_savepath'] = $th_savepath;
            $info['save_id'] = $save;
            $info['size'] = $size;
            return $info;
        } else {
            return "上传失败";
        }
    } else {
        return "没有文件";
    }
}

/**
 * 发送验证码
 * @param $account  账号
 * @param $type  类型
 * @param $sender 发送者ID 默认系统0
 * @return boolean
 * User: jinrui.wang wangjinrui2010@163.com
 * Date: 2018/8/15 9:48
 */
function getVerification ($account , $type , $sender = 0) {
    $sms = C ('SMS');
    $username = $sms['sms_key'];  //环信账号
    $password = $sms['sms_secret'];      //环信密码
    $comp = C ('WEBSITE');
    $company = $comp['website_name'];      //公司名称
    vendor ('Txunda.Txunda#Verification');

    $verifivation = new \Verification();
    $result = $verifivation->sendVerification ($account , $username , $password , $company , $type , $sender);
    if ( $result['success'] ) {
        return '发送成功，10分钟内有效';
    } else {
        return $result['error'];
    }
}

function get_vc ($num = 0 , $flag = 0) {
    /**获取验证标识**/
    $arr = array ('A' , 'B' , 'C' , 'D' , 'E' , 'F' , 'G' , 'H' , 'I' , 'J' , 'K' , 'L' , 'M' , 'N' , 'O' , 'P' , 'Q' , 'R' , 'S' , 'T' , 'U' , 'V' , 'W' , 'X' , 'Y' , 'Z' , 'a' , 'b' , 'c' , 'd' , 'e' , 'f' , 'g' , 'h' , 'i' , 'j' , 'k' , 'l' , 'm' , 'n' , 'o' , 'p' , 'q' , 'r' , 's' , 't' , 'u' , 'v' , 'w' , 'x' , 'y' , 'z' , 1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 0);
    $vc = '';
    switch ($flag) {
        case 0 :
            $s = 0;
            $e = 61;
            break;
        case 1 :
            $s = 0;
            $e = 51;
            break;
        case 2 :
            $s = 52;
            $e = 61;
            break;
    }

    for ( $i = 0; $i < $num; $i++ ) {
        $index = rand ($s , $e);
        $vc .= $arr[$index];
    }
    return $vc;
}

/**
 * 生成token
 */
function createToken () {
    $arr['token'] = md5 (time () . rand (10000 , 99999));
    $arr['expired_time'] = time () + 86400 * 7;
    return $arr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 * @return mixed
 */
function api ($name , $vars = array ()) {
    $array = explode ('/' , $name);
    $method = array_pop ($array);
    $class_name = array_pop ($array);
    $module = $array ? array_pop ($array) : 'Common';
    $callback = $module . '\\Api\\' . $class_name . 'Api::' . $method;
    if ( is_string ($vars) ) {
        parse_str ($vars , $vars);
    }
    return call_user_func_array ($callback , $vars);
}

/**
 *接口验空
 * @param null $parameter
 * @param null $keys
 * @param null $jump
 *user:jiaming.wang  459681469@qq.com
 *Date:2018/12/18 16:08
 */
function checkAppData ($parameter = null , $keys = null , $jump = null) {
    $data = $_POST;
    if ( $data == null ) {
        $data = array ();    // 无请求参数
    }
    if ( !empty($parameter) ) {
        $parameter = explode (',' , $parameter);
        $keys = explode ('-' , $keys);
        $jump = explode ('|' , $jump);
        foreach ( $parameter as $k => $v ) {
            if ( (!isset($data[$v])) || (empty($data[$v])) ) {
                if ( $jump != null ) {
                    if ( in_array ($v , $jump) ) {
                        if ( $data[$v] == '' ) {
                            apiResponse (0 , '请输入' . $keys[$k]);
                        }
                    } else {
                        if ( $data[$v] !== '0' || $data[$v] == '' ) {
                            apiResponse (0 , '请输入' . $keys[$k]);
                        }
                    }
                } else {
                    if ( $data[$v] !== '0' || $data[$v] == '' ) {
                        apiResponse (0 , '请输入' . $keys[$k]);
                    }
                }
            }
        }
    }
    return $data;
}

/**
 *验证手机号
 * @param $mobile
 *user:jiaming.wang  459681469@qq.com
 *Date:2018/12/18 16:08
 */
function isMobile ($mobile) {
    if ( !is_numeric ($mobile) ) {
        return false;
    }
    return preg_match ('#^1[0-9]{10}$#' , $mobile) ? true : false;
}

function apiResponse ($code = 0 , $message = '' , $data = array ()) {
    $response = array (
        'code' => $code ,
        'message' => $message ,
        'data' => $data
    );
    exit(json_encode ($response , JSON_UNESCAPED_UNICODE));
}

/**
 * 替换图片
 * @param $id 所查图片ID
 * @param string $type 图片类型  默认缩略图
 * @return string
 * User: jinrui.wang wangjinrui2010@163.com
 * Date: 2018/5/30 9:12
 */
function getPicPath ($id , $type = '') {
    if ( $id == '' || $id == 0 ) {
        $id = '999999';
    }
    if ( $type == 'th' ) {
        $field = 'th_savepath';
    } else {
        $field = 'path';
    }
    $url = M ('File')->where (array ('id' => $id))->getField ($field);
    if ( substr ($url , 0 , 4 == 'http') ) {
        return $url;
    }
    return C ('API_URL') . $url;
    //return C('API_URL').'/index.php'.$url;
}

/**
 * 导出数据为excel表格
 * @param $list
 * @param $filename
 * @param $indexKey
 * @param int $startRow
 * @param bool $excel2007
 * @return bool
 */
function exportExcels ($list , $indexKey , $header , $filename , $startRow = 1 , $excel2007 = false) {
    //文件引入
    vendor ("Txunda.PHPExcel.PHPExcel");
    vendor ("Txunda.PHPExcel.PHPExcel.Writer.Excel2007");

    if ( empty($filename) ) $filename = time ();
    if ( !is_array ($indexKey) ) return false;
    $header_arr = array ('A' , 'B' , 'C' , 'D' , 'E' , 'F' , 'G' , 'H' , 'I' , 'J' , 'K' , 'L' , 'M' , 'N' , 'O' , 'P' , 'Q' , 'R' , 'S' , 'T' , 'U' , 'V' , 'W' , 'X' , 'Y' , 'Z');
    //初始化PHPExcel()
    $objPHPExcel = new PHPExcel();

    //设置保存版本格式
    if ( $excel2007 ) {
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $filename = $filename . '.xlsx';
    } else {
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        $filename = $filename . '.xls';
    }

    //接下来就是写数据到表格里面去
    $objActSheet = $objPHPExcel->getActiveSheet ();
    //$startRow = 1;
    $excel_head = array ();
    foreach ( $header as $key => $value ) {
        $excel_head[$indexKey[$key]] = $value;
    }
    array_unshift ($list , $excel_head);
    foreach ( $list as $row ) {
        foreach ( $indexKey as $key => $value ) {
            //这里是设置单元格的内容
            $objPHPExcel->getDefaultStyle ()->getAlignment ()->setVertical (PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getDefaultStyle ()->getAlignment ()->setHorizontal (PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objActSheet->setCellValue ($header_arr[$key] . $startRow , $row[$value]);
            $objPHPExcel->getActiveSheet ()->getStyle ($startRow)->getNumberFormat ()
                ->setFormatCode (PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->getActiveSheet ()->getColumnDimension ($header_arr[$key])->setWidth (20);
        }
        $startRow++;
    }

    // 下载这个表格，在浏览器输出
    header ("Pragma: public");
    header ("Expires: 0");
    header ("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header ("Content-Type:application/force-download");
    header ("Content-Type:application/vnd.ms-execl");
    header ("Content-Type:application/octet-stream");
    header ("Content-Type:application/download");;
    header ('Content-Disposition:attachment;filename=' . $filename . '');
    header ("Content-Transfer-Encoding:binary");
    $objWriter->save ('php://output');
}

/**
 * 数据验证
 */
function checkData($array){
    if (empty($array)) {
        $array = array();
    } else {
        foreach ($array as $k=>$v) {
            if (is_array($v)) {
                $array[$k] = checkData($v);
            } else {
                if ($v === null || $v === NULL) {
                    $array[$k] = '';
                }
                if (is_numeric($v)) {
                    $array[$k] = (String)$v;
                }
                if (strpos($k,'price') > 0 || strpos($k,'price') === 0 || $k == 'balance') {
                    $array[$k] = number_format(round($v,2),2,'.','');
                }
            }
        }
    }
    return $array;
}