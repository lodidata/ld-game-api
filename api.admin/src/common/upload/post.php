<?php

use Slim\Http\UploadedFile;
use Logic\Admin\BaseController;

return new class extends BaseController {
    public function run()
    {
        ini_set( 'max_execution_time', '200' );
        if (empty( $_FILES ) || !isset( $_FILES['file'] ))
            return $this->lang->set( 177 );
        // 获取参数
        $settings = $this->ci->get( 'settings' )['upload'] ?? [];
        $nameType = $this->request->getParam( 'name_type', 1 );
        $dir      = trim( $this->request->getParam( 'dir', '' ), '/' );
        $file     = $_FILES['file'];
        if ($nameType == 1)
            $fileName = $this->getUploadName( $file );
        else
            $fileName = $file['name'];

        //验证文件类型及文件大小
        $type = explode( '/', $file['type'] );
        if ($type[0] == 'image')
            $fileSetting = $settings['image'];
        else
            $fileSetting = $settings['file'];
        // 获取上传的配置信息并格式化
        $fileSettings = explode( '|', $fileSetting ); // 获取文件的Map数组
        $fileSize     = explode( ':', $fileSettings[0] )[1]; // 文件大小
        $fileExtList  = explode( ',', explode( ':', $fileSettings[1] )[1] ); // 文件后缀的集合
        // 校验文件大小
        if ($file['size'] > $fileSize)
            return $this->lang->set( 178 );
        // 格式化上传文件后缀
        $fileArray = explode( '.', $file['name'] );
        $fileExt   = strtolower( end( $fileArray ) );
        if ('jpeg' == $fileExt) $fileExt = 'jpg'; // 替换文件：jpeg => jpg
        //if (!in_array( $fileExt, $fileExtList )) return $this->lang->set( 179 ); // 校验上传文件是否合法

        $obj = $settings['useDsn'];
        $res = $this->$obj( $settings['dsn'][$obj], $file, $fileName, $dir );
        return !$res ? $this->lang->set( 182 ) : $res;
    }

    /**
     * 本地上传
     *
     * @param array $config 配置项
     * @param array $file 上传的文件信息
     * @param string $fileName 目录文件
     * @return mixed
     */
    protected function local(array $config, array $file, string $fileName)
    {
        try {
            if (!is_dir( $config['imgDir'] . '/' . $config['dir'] ) && !mkdir( $config['imgDir'] . '/' . $config['dir'], 0755, true )) {
                throw new \Exception( $config['dir'] . ' 目录不存在' );
            }
            $object = $config['imgDir'] . '/' . $config['dir'] . '/' . $fileName;
            $upload = new UploadedFile( $file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'], true );
            $upload->moveTo( $object );
            return $this->lang->set( 0, [], ['url' => $config['domain'] . '/' . $config['dir'] . '/' . $fileName] );
        } catch (Exception $e) {
            return $this->lang->set( 182, [], [], ['error' => 'local":' . $e->getMessage()] );
        }
    }

    /**
     * AWS S3上传文件
     *
     * @param array $config 配置项
     * @param array $file 上传的文件信息
     * @param string $fileName 目录文件
     * @return mixed
     */
    protected function awsS3(array $config, array $file, string $fileName)
    {
        //设置超时
        set_time_limit( 0 );
        //证书 AWS access KEY ID  和  AWS secret  access KEY 替换成自己的
        $credentials = new Aws\Credentials\Credentials( $config['accessKeyId'], $config['accessKeySecret'] );
        //s3客户端
        $s3 = new Aws\S3\S3Client( [
            'version'     => 'latest',
            //地区 亚太区域（新加坡）    AWS区域和终端节点： http://docs.amazonaws.cn/general/latest/gr/rande.html
            'region'      => $config['region'],
            //加载证书
            'credentials' => $credentials,
            //开启bug调试
            'debug'       => $config['debug']
        ] );

        //存储桶 获取AWS存储桶的名称
        //需要上传的文件
        $source = $file['tmp_name']; // ROOT_PATH项目根目录，文件的本地路径例:D:/www/abc.jpg;
        $object = $config['dir'] . '/' . $fileName;
        //多部件上传
        $uploader = new Aws\S3\MultipartUploader( $s3, $source, [
            //存储桶
            'bucket'          => $config['bucket'],
            //上传后的新地址
            'key'             => $object,
            //设置访问权限  公开,不然访问不了
            'ACL'             => 'public-read',
            //分段上传
            'before_initiate' => function (\Aws\Command $command) {
                // $command is a CreateMultipartUpload operation
                $command['CacheControl'] = 'max-age=3600';
            },
            'before_upload'   => function (\Aws\Command $command) {
                // $command is an UploadPart operation
                $command['RequestPayer'] = 'requester';
            },
            'before_complete' => function (\Aws\Command $command) {
                // $command is a CompleteMultipartUpload operation
                $command['RequestPayer'] = 'requester';
            },
        ] );

        try {
            $result = $uploader->upload();
            //上传成功--返回上传后的地址
            /*$data = [
                'type' => '1',
                'data' => urldecode($result['ObjectURL'])
            ];*/
            return $this->lang->set( 0, [], ['url' => $config['domain'] . '/' . $object] );
        } catch (Aws\Exception\MultipartUploadException $e) {
            return $this->lang->set( 182, [], [], ['error' => 's3":' . $e->getMessage()] );
        }
    }

    /**
     * 取得上传后文件名称
     *
     * @param $file
     * @return string
     */
    protected function getUploadName($file): string
    {
        $temp    = explode( '.', $file['name'] );
        $fileExt = strtolower( end( $temp ) );
        return md5( time() . mt_rand( 0, 999999 ) ) . 'upload' . $fileExt;
    }

    /**
     * 移除临时文件
     *
     * @param $file
     * @return void
     */
    protected function remove($file)
    {
        @unlink( $file['tmp_name'] );
    }
};
