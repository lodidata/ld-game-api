<?php


namespace Lib\Validate;

use Illuminate\Support\Facades\Session;
use Lib\Exception\ClassNotFoundException;
use Illuminate\Database\Capsule\Manager as Db;

class Validate
{
    // 实例
    protected static $instance;

    // 自定义的验证类型
    protected static $type = [];

    // 验证类型别名
    protected $alias = [
        '>' => 'gt', '>=' => 'egt', '<' => 'lt', '<=' => 'elt', '=' => 'eq', 'same' => 'eq',
    ];

    // 当前验证的规则
    protected $rule = [];

    // 验证提示信息

    protected $message = [];

    // 验证字段描述
    protected $field = [];

    // 验证规则默认提示信息
    protected static $typeMsg = [
        'require'   => ':attribute cannot be empty',
        'number'    => ':attribute must be a number',
        'integer'   => ':attribute must be a number',
        'float'     => ':attribute must be a floating point number',
        'boolean'   => ':attribute must be boolean',
        'email'     => ':attribute format does not match',
        'array'     => ':attribute must be an array',
        'accepted'  => ':attribute must be yes, on or 1',
        'date '     => ':attribute format does not match ',
        'file'      => ':attribute is not a valid Upload file',
        'image'     => ':attribute is not a valid image file',
        'alpha'     => ':attribute can only be letters',
        'alphaNum'  => ':attribute can only be letters and numbers',
        'alphaDash' => ':attribute can only be letters, numbers and underscores_ And dash - ',

        'activeUrl'   => ':attribute is not a valid domain name or IP',
        'chs'         => ':attribute can only be ChineseValidate characters',
        'chsAlpha'    => ':attribute can only be ChineseValidate characters and letters',
        'chsAlphaNum' => ':attribute can only be ChineseValidate characters, letters and numbers',
        'chsDash'     => ':attribute can only be ChineseValidate characters, letters, numbers and underscores_ And dash - ',


        'url'         => ':attribute is not a valid URL address',
        'ip'          => ':attribute is not a valid IP address',
        'dateFormat ' => ':attribute must use the date format :rule',
        'in'          => ':attribute must be in the range of :rule',
        'notIn'       => ':attribute cannot be in the range of :rule',
        'between'     => ':attribute can only be between: 1 - :2',
        'notBetween'  => ':attribute cannot be between: 1 - :2',
        'length'      => ':attribute length does not meet the requirements :rule',
        'max '        => ':attribute length cannot exceed :rule',
        'min '        => ':attribute length cannot be less than :rule',
        'after'       => ':attribute date cannot be less than :rule',
        'before'      => ':attribute date cannot exceed :rule',
        'expire'      => 'Not within the validity period :rule',
        'allowIp'     => 'IP access not allowed',
        'denyIp '     => 'Prohibited IP access',
        'confirm'     => ':attribute and confirmation field :2 are inconsistent',
        'different '  => ':attribute and comparison field :2 cannot be the same',
        'egt'         => ':attribute must be greater than or equal to :rule',
        'gt'          => ':attribute must be greater than :rule',
        'elt'         => ':attribute must be less than or equal to :rule',
        'lt'          => ':attribute must be less than :rule',
        'eq'          => ':attribute must be equal to :rule',
        'unique'      => ':attribute already exists',
        'regex '      => ':attribute does not conform to the specified rules',
        'method '     => 'Invalid request type',
        'token '      => 'Invalid token data',
        'fileSize'    => 'The uploaded file size does not match',
        'fileExt'     => 'The Upload file suffix does not match',
        'fileMime'    => 'The Upload file type does not match',
    ];

    // 当前验证场景
    protected $currentScene = null;

    // 正则表达式 regex = ['zip'=>'\d{6}',...]
    protected $regex = [];

    // 验证场景 scene = ['edit'=>'name1,name2,...']
    protected $scene = [];

    // 验证失败错误信息
    protected $error = [];

    // 批量验证
    protected $batch = false;

    /**
     * 构造函数
     *
     * @access public
     * @param array $rules 验证规则
     * @param array $message 验证提示信息
     * @param array $field 验证字段描述信息
     */
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        $this->rule    = array_merge( $this->rule, $rules );
        $this->message = array_merge( $this->message, $message );
        $this->field   = array_merge( $this->field, $field );
    }

    /**
     * 实例化验证
     *
     * @access public
     * @param array $rules 验证规则
     * @param array $message 验证提示信息
     * @param array $field 验证字段描述信息
     * @return Validate
     */
    public static function make(array $rules = [], array $message = [], array $field = []): Validate
    {
        if (is_null( self::$instance )) {
            self::$instance = new self( $rules, $message, $field );
        }
        return self::$instance;
    }

    /**
     * 添加字段验证规则
     *
     * @access protected
     * @param string|array $name :字段名称或者规则数组
     * @param mixed $rule :验证规则
     * @return Validate
     */
    public function rule($name, $rule = ''): Validate
    {
        if (is_array( $name )) {
            $this->rule = array_merge( $this->rule, $name );
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * 注册验证（类型）规则
     *
     * @access public
     * @param string|array $type :验证规则类型
     * @param mixed $callback :callback方法(或闭包)
     * @return void
     */
    public static function extend($type, $callback = null)
    {
        if (is_array( $type )) {
            self::$type = array_merge( self::$type, $type );
        } else {
            self::$type[$type] = $callback;
        }
    }

    /**
     * 设置验证规则的默认提示信息
     *
     * @access protected
     * @param string|array $type :验证规则类型名称或者数组
     * @param string $msg :验证提示信息
     * @return void
     */
    public static function setTypeMsg($type, string $msg = '')
    {
        if (is_array( $type )) {
            self::$typeMsg = array_merge( self::$typeMsg, $type );
        } else {
            self::$typeMsg[$type] = $msg;
        }
    }

    /**
     * 设置提示信息
     *
     * @access public
     * @param string|array $name :字段名称
     * @param string $message :提示信息
     * @return Validate
     */
    public function message($name, string $message = ''): Validate
    {
        if (is_array( $name )) {
            $this->message = array_merge( $this->message, $name );
        } else {
            $this->message[$name] = $message;
        }
        return $this;
    }

    /**
     * 设置验证场景
     *
     * @access public
     * @param string|array $name :场景名或者场景设置数组
     * @param mixed $fields :要验证的字段
     * @return Validate
     */
    public function scene($name, $fields = null): Validate
    {
        if (is_array( $name )) {
            $this->scene = array_merge( $this->scene, $name );
        }
        if (is_null( $fields )) {
            // 设置当前场景
            $this->currentScene = $name;
        } else {
            // 设置验证场景
            $this->scene[$name] = $fields;
        }
        return $this;
    }

    /**
     * 判断是否存在某个验证场景
     *
     * @access public
     * @param string $name :场景名
     * @return bool
     */
    public function hasScene(string $name = ''): bool
    {
        if (!$name)
            return isset( $this->scene[$name] );
        return false;
    }

    /**
     * 设置批量验证
     *
     * @access public
     * @param bool $batch :是否批量验证
     * @return Validate
     */
    public function batch(bool $batch = true): Validate
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * 数据自动验证
     *
     * @access public
     * @param array $data :数据
     * @param mixed $rules :验证规则
     * @param string $scene :验证场景
     * @return bool
     */
    public function check(array $data, $rules = [], string $scene = ''): bool
    {
        $this->error = [];


        if (empty( $rules )) {
            // 读取验证规则
            $rules = $this->rule;
        }

        // 分析验证规则
        $scene = $this->getScene( $scene );

        // 处理场景验证字段
        $change = [];
        $array  = [];
        foreach ($scene as $k => $val) {
            if (is_numeric( $k )) {
                $array[] = $val;
            } else {
                $array[]    = $k;
                $change[$k] = $val;
            }
        }

        foreach ($rules as $key => $item) {
            // field => rule1|rule2... field=>['rule1','rule2',...]
            if (is_numeric( $key )) {
                // [field,rule1|rule2,msg1|msg2]
                $key  = $item[0];
                $rule = $item[1];
                if (isset( $item[2] )) {
                    $msg = is_string( $item[2] ) ? explode( '|', $item[2] ) : $item[2];
                } else {
                    $msg = [];
                }
            } else {
                $rule = $item;
                $msg  = [];
            }
            if (strpos( $key, '|' )) {
                // 字段|描述 用于指定属性名称
                [$key, $title] = explode( '|', $key );
            } else {
                $title = $this->field[$key] ?? $key;
            }

            // 场景检测
            if (!empty( $scene )) {
                if ($scene instanceof \Closure && !call_user_func_array( $scene, [$key, $data] )) {
                    continue;
                } else if (is_array( $scene )) {
                    if (!in_array( $key, $array )) {
                        continue;
                    } else if (isset( $change[$key] )) {
                        // 重载某个验证规则
                        $rule = $change[$key];
                    }
                }
            }

            // 获取数据 支持二维数组
            $value = $this->getDataValue( $data, $key );

            // 字段验证
            if ($rule instanceof \Closure) {
                // 匿名函数验证 支持传入当前字段和所有字段两个数据
                $result = call_user_func_array( $rule, [$value, $data] );
            } else {
                $result = $this->checkItem( $key, $value, $rule, $data, $title, $msg );
            }

            if (true !== $result) {
                // 没有返回true 则表示验证失败
                if (!empty( $this->batch )) {
                    // 批量验证
                    if (is_array( $result )) {
                        $this->error = array_merge( $this->error, $result );
                    } else {
                        $this->error[$key] = $result;
                    }
                } else {
                    $this->error = $result;
                    return false;
                }
            }
        }
        return empty( $this->error );
    }

    /**
     * 验证单个字段规则
     *
     * @access protected
     * @param string $field :字段名
     * @param mixed $value :字段值
     * @param mixed $rules :验证规则
     * @param array $data :数据
     * @param string $title :字段描述
     * @param array $msg :提示信息
     * @return mixed
     */
    protected function checkItem(string $field, $value, $rules, array $data, string $title = '', array $msg = [])
    {
        $result = $i = 0;

        // 支持多规则验证 require|in:a,b,c|... 或者 ['require','in'=>'a,b,c',...]
        if (is_string( $rules )) {
            $rules = explode( '|', $rules );
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof \Closure) {
                $result = call_user_func_array( $rule, [$value, $data] );
                $info   = is_numeric( $key ) ? '' : $key;
            } else {
                // 判断验证类型
                if (is_numeric( $key )) {
                    if (strpos( $rule, ':' )) {
                        [$type, $rule] = explode( ':', $rule, 2 );
                        if (isset( $this->alias[$type] )) {
                            // 判断别名
                            $type = $this->alias[$type];
                        }
                        $info = $type;
                    } else if (method_exists( $this, $rule )) {
                        $type = $rule;
                        $info = $rule;
                        $rule = '';
                    } else {
                        $type = 'is';
                        $info = $rule;
                    }
                } else {
                    $info = $type = $key;
                }

                // 如果不是require 有数据才会行验证
                if (0 === strpos( $info, 'require' ) || (!is_null( $value ) && '' !== $value)) {
                    // 验证类型
                    $callback = self::$type[$type] ?? [$this, $type];
                    // 验证数据
                    $result = call_user_func_array( $callback, [$value, $rule, $data, $field, $title] );
                } else {
                    $result = true;
                }
            }

            if (false === $result) {
                // 验证失败 返回错误信息
                if (isset( $msg[$i] )) {
                    $message = $msg[$i];
                    if (is_string( $message ) && strpos( $message, '{%' ) === 0) {
                        $message = LangLib::get( substr( $message, 2, -1 ) );
                    }
                } else {
                    $message = $this->getRuleMsg( $field, $title, $info, $rule );
                }
                return $message;
            } else if (true !== $result) {
                // 返回自定义错误信息
                if (is_string( $result ) && false !== strpos( $result, ':' )) {
                    $result = str_replace( [':attribute', ':rule'], [$title, (string)$rule], $result );
                }
                return $result;
            }
            $i++;
        }
        return $result;
    }

    /**
     * 验证是否和某个字段的值一致
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @param string $field :字段名
     * @return bool
     */
    protected function confirm($value, $rule, array $data, string $field = ''): bool
    {
        if ('' == $rule) {
            if (strpos( $field, '_confirm' )) {
                $rule = strstr( $field, '_confirm', true );
            } else {
                $rule = $field . '_confirm';
            }
        }
        return $this->getDataValue( $data, $rule ) === $value;
    }

    /**
     * 验证是否和某个字段的值是否不同
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function different($value, $rule, array $data): bool
    {
        return $this->getDataValue( $data, $rule ) != $value;
    }

    /**
     * 验证是否大于等于某个值
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function egt($value, $rule, array $data): bool
    {
        $val = $this->getDataValue( $data, $rule );
        return !is_null( $val ) && $value >= $val;
    }

    /**
     * 验证是否大于某个值
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function gt($value, $rule, array $data): bool
    {
        $val = $this->getDataValue( $data, $rule );
        return !is_null( $val ) && $value > $val;
    }

    /**
     * 验证是否小于等于某个值
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function elt($value, $rule, array $data): bool
    {
        $val = $this->getDataValue( $data, $rule );
        return !is_null( $val ) && $value <= $val;
    }

    /**
     * 验证是否小于某个值
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function lt($value, $rule, array $data): bool
    {
        $val = $this->getDataValue( $data, $rule );
        return !is_null( $val ) && $value < $val;
    }

    /**
     * 验证是否等于某个值
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function eq($value, $rule): bool
    {
        return $value == $rule;
    }

    /**
     * 验证字段值是否为有效格式
     *
     * @access protected
     * @param mixed $value :字段值
     * @param string $rule :验证规则
     * @param array $data :验证数据
     * @return bool
     */
    protected function is($value, string $rule, array $data = []): bool
    {
        switch ($rule) {
            case 'require':
                // 必须
                $result = !empty( $value ) || '0' == $value;
                break;
            case 'accepted':
                // 接受
                $result = in_array( $value, ['1', 'on', 'yes'] );
                break;
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime( $value );
                break;
            case 'alpha':
                // 只允许字母
                $result = $this->regex( $value, '/^[A-Za-z]+$/' );
                break;
            case 'alphaNum':
                // 只允许字母和数字
                $result = $this->regex( $value, '/^[A-Za-z0-9]+$/' );
                break;
            case 'alphaDash':
                // 只允许字母、数字和下划线 破折号
                $result = $this->regex( $value, '/^[A-Za-z0-9\-\_]+$/' );
                break;
            case 'chs':
                // 只允许汉字
                $result = $this->regex( $value, '/^[\x{4e00}-\x{9fa5}]+$/u' );
                break;
            case 'chsAlpha':
                // 只允许汉字、字母
                $result = $this->regex( $value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u' );
                break;
            case 'chsAlphaNum':
                // 只允许汉字、字母和数字
                $result = $this->regex( $value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u' );
                break;
            case 'chsDash':
                // 只允许汉字、字母、数字和下划线_及破折号-
                $result = $this->regex( $value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u' );
                break;
            case 'activeUrl':
                // 是否为有效的网址
                $result = checkdnsrr( $value );
                break;
            case 'ip':
                // 是否为IP地址
                $result = $this->filter( $value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6] );
                break;
            case 'url':
                // 是否为一个URL地址
                $result = $this->filter( $value, FILTER_VALIDATE_URL );
                break;
            case 'float':
                // 是否为float
                $result = $this->filter( $value, FILTER_VALIDATE_FLOAT );
                break;
            case 'number':
                $result = is_numeric( $value );
                break;
            case 'integer':
                // 是否为整型
                $result = $this->filter( $value, FILTER_VALIDATE_INT );
                break;
            case 'email':
                // 是否为邮箱地址
                $result = $this->filter( $value, FILTER_VALIDATE_EMAIL );
                break;
            case 'boolean':
                // 是否为布尔值
                $result = in_array( $value, [true, false, 0, 1, '0', '1'], true );
                break;
            case 'array':
                // 是否为数组
                $result = is_array( $value );
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array( $this->getImageType( $value->getRealPath() ), [1, 2, 3, 6] );
                break;
            case 'token':
                $result = $this->token( $value, '__token__', $data );
                break;
            default:
                if (isset( self::$type[$rule] )) {
                    // 注册的验证规则
                    $result = call_user_func_array( self::$type[$rule], [$value] );
                } else {
                    // 正则验证
                    $result = $this->regex( $value, $rule );
                }
        }
        return $result;
    }

    // 判断图像类型
    protected function getImageType($image)
    {
        if (function_exists( 'exif_imagetype' )) {
            return exif_imagetype( $image );
        } else {
            try {
                $info = getimagesize( $image );
                return $info ? $info[2] : false;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * 验证是否为合格的域名或者IP 支持A，MX，NS，SOA，PTR，CNAME，AAAA，A6， SRV，NAPTR，TXT 或者 ANY类型
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function activeUrl($value, $rule): bool
    {
        if (!in_array( $rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY'] )) {
            $rule = 'MX';
        }
        return checkdnsrr( $value, $rule );
    }

    /**
     * 验证是否有效IP
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则 ipv4 ipv6
     * @return bool
     */
    protected function ip($value, $rule): bool
    {
        if (!in_array( $rule, ['ipv4', 'ipv6'] )) {
            $rule = 'ipv4';
        }
        return $this->filter( $value, [FILTER_VALIDATE_IP, 'ipv6' == $rule ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4] );
    }

    /**
     * 验证上传文件后缀
     *
     * @access protected
     * @param mixed $file :上传文件
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function fileExt($file, $rule): bool
    {
        if (!($file instanceof File)) {
            return false;
        }
        if (is_string( $rule )) {
            $rule = explode( ',', $rule );
        }

        return $file->checkExt( $rule );
    }


    /**
     * 验证上传文件类型
     *
     * @access protected
     * @param mixed $file :上传文件
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function fileMime($file, $rule): bool
    {
        if (!($file instanceof File)) {
            return false;
        }
        if (is_string( $rule )) {
            $rule = explode( ',', $rule );
        }

        return $file->checkMime( $rule );
    }


    /**
     * 验证上传文件大小
     *
     * @access protected
     * @param mixed $file :上传文件
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function fileSize($file, $rule): bool
    {
        if (!($file instanceof File)) {
            return false;
        }

        return $file->checkSize( $rule );
    }


    /**
     * 验证图片的宽高及类型
     *
     * @access protected
     * @param mixed $file :上传文件
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function image($file, $rule): bool
    {
        if (!($file instanceof File)) {
            return false;
        }
        if ($rule) {
            $rule = explode( ',', $rule );
            [$width, $height, $type] = getimagesize( $file->getRealPath() );
            if (isset( $rule[2] )) {
                $imageType = strtolower( $rule[2] );
                if ('jpeg' == $imageType) {
                    $imageType = 'jpg';
                }
                if (image_type_to_extension( $type, false ) != $imageType) {
                    return false;
                }
            }

            [$w, $h] = $rule;
            return $w == $width && $h == $height;
        } else {
            return in_array( $this->getImageType( $file->getRealPath() ), [1, 2, 3, 6] );
        }
    }

    /**
     * 验证请求类型
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function method($value, $rule): bool
    {
        $method = Request::instance()->method();
        return strtoupper( $rule ) == $method;
    }

    /**
     * 验证时间和日期是否符合指定格式
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function dateFormat($value, $rule): bool
    {
        $info = date_parse_from_format( $rule, $value );
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * 验证是否唯一
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则 格式：数据表,字段名,排除ID,主键名,命名空间
     * @param array $data :数据
     * @param string $field :验证字段名
     * @return bool
     */
    protected function unique($value, $rule, array $data, string $field): bool
    {

        $map = [];
        if (is_string( $rule ))
            $rule = explode( ',', $rule );


        if (false !== strpos( $rule[0], '\\' )) {
            // 指定模型类
            $db = new $rule[0];
        } else {
            try {
                $namespace = strval( $rule[4] ?? '' );
                $db        = $this->model( $rule[0], $namespace );
            } catch (ClassNotFoundException $e) {
                $db = Db::table( $rule[0] );
            }
        }

        $key = $rule[1] ?? $field;

        if (strpos( $key, '^' )) {
            // 支持多个字段验证
            $fields = explode( '^', $key );
            foreach ($fields as $key) {
                if (isset( $data[$key] )) {
                    $map[$key] = $data[$key];
                } else {
                    $map[$key] = $data[$field];
                }

            }
        } else if (strpos( $key, '=' )) {
            parse_str( $key, $map );
        } else {
            $map[$key] = $data[$field];
        }

        $pk = strval( !empty( $rule[3] ) ? $rule[3] : 'id' );
        if (!empty( $rule[2] )) {
            $map[$pk] = ['<>', $rule[2]];
        } else if (isset( $data[$pk] ) && isPositiveInteger( $data[$pk] )) {
            $map[$pk] = ['<>', $data[$pk]];
        }
        foreach ($map as $k => $v) {
            if (is_array( $v )) {
                $db = $db->where( $k, $v[0], $v[1] );
            } else {
                $db = $db->where( $k, $v );
            }
        }

        if ($db->value( $pk )) {
            return false;
        }

        return true;
    }

    /**
     * 使用行为类验证
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return mixed
     */
    protected function behavior($value, $rule, array $data)
    {
        return Hook::exec( $rule, '', $data );
    }

    /**
     * 使用filter_var方式验证
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function filter($value, $rule): bool
    {
        if (is_string( $rule ) && strpos( $rule, ',' )) {
            [$rule, $param] = explode( ',', $rule );
        } else if (is_array( $rule )) {
            $param = $rule[1] ?? null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }
        return false !== filter_var( $value, is_int( $rule ) ? $rule : filter_id( $rule ), $param );
    }

    /**
     * 验证某个字段等于某个值的时候必须
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function requireIf($value, $rule, array $data): bool
    {
        [$field, $val] = explode( ',', $rule, 2 );
        $valArr = explode( ',', $val );//2018-04-11修改 支持多个验证
        if (in_array( $this->getDataValue( $data, $field ), $valArr )) {
            return !empty( $value ) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 通过回调方法验证某个字段是否必须
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function requireCallback($value, $rule, array $data): bool
    {
        $result = call_user_func_array( $rule, [$value, $data] );
        if ($result) {
            return !empty( $value ) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 验证某个字段有值的情况下必须
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function requireWith($value, $rule, array $data): bool
    {
        $val = $this->getDataValue( $data, $rule );
        if (!empty( $val )) {
            return !empty( $value ) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * 验证是否在范围内
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function in($value, $rule): bool
    {
        return in_array( $value, is_array( $rule ) ? $rule : explode( ',', $rule ) );
    }

    /**
     * 验证是否不在某个范围
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function notIn($value, $rule): bool
    {
        return !in_array( $value, is_array( $rule ) ? $rule : explode( ',', $rule ) );
    }

    /**
     * between验证数据
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function between($value, $rule): bool
    {
        if (is_string( $rule )) {
            $rule = explode( ',', $rule );
        }
        [$min, $max] = $rule;
        return $value >= $min && $value <= $max;
    }

    /**
     * 使用notbetween验证数据
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function notBetween($value, $rule): bool
    {
        if (is_string( $rule )) {
            $rule = explode( ',', $rule );
        }
        [$min, $max] = $rule;
        return $value < $min || $value > $max;
    }

    /**
     * 验证数据长度
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function length($value, $rule): bool
    {
        if (is_array( $value )) {
            $length = count( $value );
        } else if ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen( (string)$value );
        }

        if (strpos( $rule, ',' )) {
            // 长度区间
            [$min, $max] = explode( ',', $rule );
            return $length >= $min && $length <= $max;
        } else {
            // 指定长度
            return $length == $rule;
        }
    }

    /**
     * 验证数据最大长度
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function max($value, $rule): bool
    {
        if (is_array( $value )) {
            $length = count( $value );
        } else if ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen( (string)$value );
        }
        return $length <= $rule;
    }

    /**
     * 验证数据最小长度
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function min($value, $rule): bool
    {
        if (is_array( $value )) {
            $length = count( $value );
        } else if ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen( (string)$value );
        }
        return $length >= $rule;
    }

    /**
     * 验证日期
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function after($value, $rule): bool
    {
        return strtotime( $value ) >= strtotime( $rule );
    }

    /**
     * 验证日期
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function before($value, $rule): bool
    {
        return strtotime( $value ) <= strtotime( $rule );
    }

    /**
     * 验证有效期
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function expire($value, $rule): bool
    {
        if (is_string( $rule )) {
            $rule = explode( ',', $rule );
        }
        [$start, $end] = $rule;
        if (!is_numeric( $start )) {
            $start = strtotime( $start );
        }

        if (!is_numeric( $end )) {
            $end = strtotime( $end );
        }
        return $_SERVER['REQUEST_TIME'] >= $start && $_SERVER['REQUEST_TIME'] <= $end;
    }

    /**
     * 验证IP许可
     *
     * @access protected
     * @param string $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function allowIp(string $value, $rule): bool
    {
        return in_array( $_SERVER['REMOTE_ADDR'], is_array( $rule ) ? $rule : explode( ',', $rule ) );
    }

    /**
     * 验证IP禁用
     *
     * @access protected
     * @param string $value :字段值
     * @param mixed $rule :验证规则
     * @return bool
     */
    protected function denyIp(string $value, $rule): bool
    {
        return !in_array( $_SERVER['REMOTE_ADDR'], is_array( $rule ) ? $rule : explode( ',', $rule ) );
    }

    /**
     * 使用正则验证数据
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则 正则规则或者预定义正则名
     * @return bool
     */
    protected function regex($value, $rule): bool
    {
        if (isset( $this->regex[$rule] )) {
            $rule = $this->regex[$rule];
        }
        if (0 !== strpos( $rule, '/' ) && !preg_match( '/\/[imsU]{0,4}$/', $rule )) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }
        return 1 === preg_match( $rule, (string)$value );
    }

    /**
     * 验证表单令牌
     *
     * @access protected
     * @param mixed $value :字段值
     * @param mixed $rule :验证规则
     * @param array $data :数据
     * @return bool
     */
    protected function token($value, $rule, array $data): bool
    {
        $rule = !empty( $rule ) ? $rule : '__token__';
        if (!isset( $data[$rule] ) || !Session::has( $rule )) {
            // 令牌数据无效
            return false;
        }

        // 令牌验证
        if (Session::get( $rule ) === $data[$rule]) {
            // 防止重复提交
            Session::delete( $rule ); // 验证完成销毁session
            return true;
        }
        // 开启TOKEN重置
        Session::delete( $rule );
        return false;
    }

    // 获取错误信息
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取数据值
     *
     * @access protected
     * @param array $data :数据
     * @param string $key :数据标识 支持二维
     * @return mixed
     */
    protected function getDataValue(array $data, string $key)
    {
        if (is_numeric( $key )) {
            $value = $key;
        } else if (strpos( $key, '.' )) {
            // 支持二维数组验证
            [$name1, $name2] = explode( '.', $key );
            $value = $data[$name1][$name2] ?? null;
        } else {
            $value = $data[$key] ?? null;
        }
        return $value;
    }

    /**
     * 获取验证规则的错误提示信息
     *
     * @access protected
     * @param string $attribute :字段英文名
     * @param string $title :字段描述名
     * @param string $type :验证规则名称
     * @param mixed $rule :验证规则数据
     * @return string
     */
    protected function getRuleMsg(string $attribute, string $title, string $type, $rule): string
    {
        if (isset( $this->message[$attribute . '.' . $type] )) {
            $msg = $this->message[$attribute . '.' . $type];
        } else if (isset( $this->message[$attribute][$type] )) {
            $msg = $this->message[$attribute][$type];
        } else if (isset( $this->message[$attribute] )) {
            $msg = $this->message[$attribute];
        } else if (isset( self::$typeMsg[$type] )) {
            $msg = self::$typeMsg[$type];
        } else if (0 === strpos( $type, 'require' )) {
            $msg = self::$typeMsg['require'];
        } else {
            $msg = $title . '规则错误';
        }

        if (is_string( $msg ) && 0 === strpos( $msg, '{%' )) {
            $msg = LangLib::get( substr( $msg, 2, -1 ) );
        }

        if (is_string( $msg ) && is_scalar( $rule ) && false !== strpos( $msg, ':' )) {
            // 变量替换
            if (is_string( $rule ) && strpos( $rule, ',' )) {
                $array = array_pad( explode( ',', $rule ), 3, '' );
            } else {
                $array = array_pad( [], 3, '' );
            }
            $msg = str_replace(
                [':attribute', ':rule', ':1', ':2', ':3'],
                [$title, (string)$rule, $array[0], $array[1], $array[2]],
                $msg );
        }
        return $msg;
    }

    /**
     * 获取数据验证的场景
     *
     * @access protected
     * @param string $scene :验证场景
     * @return array
     */
    protected function getScene(string $scene = ''): array
    {
        if (empty( $scene )) {
            // 读取指定场景
            $scene = $this->currentScene;
        }

        if (!empty( $scene ) && isset( $this->scene[$scene] )) {
            // 如果设置了验证适用场景
            $scene = $this->scene[$scene];
            if (is_string( $scene )) {
                $scene = explode( ',', $scene );
            }
        } else {
            $scene = [];
        }
        return $scene;
    }

    public static function __callStatic($method, $params)
    {
        $class = self::make();
        if (method_exists( $class, $method )) {
            return call_user_func_array( [$class, $method], $params );
        } else {
            throw new \BadMethodCallException( 'method not exists:' . __CLASS__ . '->' . $method );
        }
    }

    /**
     * 实例化（分层）模型
     *
     * @param string $name :Model名称
     * @param string $namespace : 业务层名称
     * @return object
     * @throws ClassNotFoundException
     */
    public static function model(string $name = '', string $namespace = ''): object
    {
        $guid = $name . $namespace;
        if (isset( self::$instance[$guid] )) {
            return self::$instance[$guid];
        }
        if ($namespace) {
            $class = '\\Model\\' . $namespace . '\\' . $name;
        } else {
            $class = '\\Model\\' . $name;
        }
        if (class_exists( $class )) {
            $model = new $class();
        } else {
            throw new ClassNotFoundException( 'class not exists:' . $class, $class );
        }

        self::$instance[$guid] = $model;
        return $model;
    }
}
