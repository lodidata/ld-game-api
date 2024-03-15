<?php

namespace Lib\Validate;


use Lib\Exception\ParamsException;

class BaseValidate extends Validate
{
    /**
     * 用于对参数进行批量校验
     *
     * @param string $scene 支持场景校验
     * @param $request
     * @param $response
     * @param bool $batch
     * @return bool
     * @throws \Lib\Exception\ParamsException
     * @throws \Exception
     */
    public function paramsCheck(string $scene, $request, $response, bool $batch = false): bool
    {
        $params = $request->getParams(); // 获取所有参数
        $result = $this->scene( $scene )->batch( $batch )->check( $params ); // 批量校验
        if (!$result) {
            $newResponse = createResponse( $response, 200, 10, $this->error );
            throw new ParamsException( $request, $newResponse );
        }

        return true;
    }

    // 不允许为空
    protected function isNotEmpty($value, $rule = '', $data = '', $field = '')
    {
        if (empty( $value )) {
            return $field . '不允许为空';
        } else {
            return true;
        }
    }

    // 必须是正整数
    protected function isPositiveInteger($value, $rule = '', $data = '', $field = '')
    {
        if (isPositiveInteger( $value )) {
            return true;
        }
        return $field . '必须是正整数';
    }

    /**
     * 按照正则来判断参数是否合法
     *
     * @param $value
     * @param string $rule
     * @param string $data
     * @param string $field
     * @return bool|string
     */
    protected function checkValueByRegex($value, string $rule = '', string $data = '', string $field = '')
    {
        if (empty( $value )) {
            return false;
        }

        return regex( $value, $rule );
    }
}