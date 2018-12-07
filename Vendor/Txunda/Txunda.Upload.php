<?php
/**
 * Created by PhpStorm.
 * User: 木公子
 * Date: 2018/7/23
 * Time: 15:16
 */

class Upload
{
    protected $fileInfo; // 上传后的文件信息
    protected $errorInfo; // 错误信息
    //
    public function uploadFile($files=array()) {
        $_files = empty($files) ? $_FILES : $files;

    }

    /**
     * 设置报错信息
     * User: 木
     * Date: 2018/7/23 15:41
     * @param string $e
     * @return bool
     */
    protected function setError($e='') {
        $this->errorInfo = $e;
        return false;
    }

    /**
     * 获取错误信息
     * User: 木
     * Date: 2018/7/23 15:41
     * @return mixed
     */
    public function getError() {
        return $this->errorInfo;
    }

    public function getFileInfo() {
        return $this->fileInfo;
    }
}