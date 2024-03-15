<?php

use Logic\Admin\BaseController;
use Lib\Validate\Admin\DownloadValidate;

/**
 * 下载
 */
return new class() extends BaseController {
    public function run()
    {
        (new DownloadValidate())->paramsCheck('get', $this->request, $this->response);
        $params = $this->request->getParams();
        // 账单
        if ($params['name'] == 'bill') {
            $data       = [];
            $field      = ['代理账号', '品牌名称', '游戏厂商', '账单(起)日期', '账单(止)日期', '货币类型', '有效投注', '输赢', '费率', '汇率', '交收金额'];
            $config     = [
                'path' => getTmpDir(),
            ];
            $fileName   = 'bill.xlsx';
            $xlsxObject = new \Vtiful\Kernel\Excel($config);
            // Init File
            $fileObject = $xlsxObject->fileName($fileName);
            // AutPut
            $filePath = $fileObject
                ->header($field)
                ->data($data)
                ->output();
            // Set Header
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            ob_clean();
            flush();
            if (copy($filePath, 'php://output') === false) {
                // Throw exception
                return $this->lang->set(147);
            }
            // Delete temporary file
            @unlink($filePath);
            die;
        }
        return $this->lang->set(146);
    }
};
