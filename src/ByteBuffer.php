<?php
namespace chawuciren;

class ByteBuffer
{
    // 长度
    public $length;
    // 数组
    public $buffer;
    // 偏移
    public $offset;
    // 类名和命名空间
    protected $currentFullClassName = '';

    /**
     * @brief 构造
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return
     */
    public function __construct($data = '', $length = 0)
    {
        $this->length = 0;
        $this->buffer = [];
        $this->offset = 0;

        $this->valueOf($data, $length);
    }

    /**
     * @brief 构造一个新的 ByteBuffer 实例
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return ByteBuffer
     */
    public static function from($data = '', $length = 0)
    {
        return new self($data, $length);
    }

    /**
     * @brief 初始化成员变量中的完整类名和命名空间
     *
     * @return null
     */
    public function initCurrentFullClassName()
    {
        if (empty($this->currentFullClassName)) {
            $this->currentFullClassName = get_class();
        }
    }

    /**
     * @brief 设一个值到 ByteBuffer实例中
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return  ByteBuffer
     */
    public function valueOf($data = '', $length = 0)
    {
        // 初始化类名
        $this->initCurrentFullClassName();

        // 初始化buffer
        if (empty($this->buffer)) {
            $this->buffer = [];
        }

        if (empty($length) && (is_int($data) || is_float($data) || is_double($data) || is_long($data))) {
            // 指定大小
            $length = $data;
            $data   = null;
        } else if (is_array($data)) {
            // 传入数组类型
            $this->offset = 0;
            $this->buffer = $data;
            $this->length = count($data);

            return $this;
        } else if (is_object($data) && get_class($data) == $this->currentFullClassName) {
            // 传入ByteBuffer类型
            $this->offset = 0;
            $this->buffer = $data->buffer;
            $this->length = $data->length;

            return $this;
        }

        // 将数据切片
        $buffer = unpack('C*', $data);
        if ($length > 0) {
            $buffer = array_slice($buffer, 0, $length);
        } else {
            $length = count($buffer);
            $buffer = array_slice($buffer, 0, $length);
        }

        $buffer       = array_pad($buffer, $length, 0);
        $this->length = $length;
        $this->buffer = $buffer;

        return $this;
    }

    /**
     * @brief 返回当前实例的String形式的数据
     *
     * @return String
     */
    public function toString()
    {
        $buffer = '';
        $length = $this->length;
        for ($index = 0; $index < $length; $index++) {
            $buffer .= pack('C', $this->buffer[$offset + $index]);
        }

        return $buffer;
    }

    /**
     * @brief 从 Buffer 中读取特定格式的数据
     *
     * @param $format 格式
     * @param $offset 偏移量
     * @param $length 长度
     *
     * @return Bool/BigNumber
     */
    protected function readValue($format = 'C', $offset = 0, $length = 1)
    {
        if ($offset === null) {
            $offset = $this->offset;
        }

        if ($offset < 0 || ($offset + $length) > $this->length) {
            return false;
        }

        $buffer = '';
        for ($index = 0; $index < $length; $index++) {
            $buffer .= pack('C', $this->buffer[$offset + $index]);
        }

        $this->offset = $offset + $length;
        $value        = unpack($format, $buffer);
        if (!empty($value) && is_array($value) && count($value) == 1) {
            $value = $value[1];
            $value = new \chawuciren\BigNumber($value);
        } else {
            $value = false;
        }

        return $value;
    }

    /**
     * @brief 写数据到 Buffer 中
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $format 格式
     * @param $offset 偏移
     * @param $length 长度
     *
     * @return
     */
    protected function writeValue($value = 0, $format = 'C', $offset = 0, $length = 1)
    {
        if ($offset === null) {
            $offset = $this->offset;
        }

        if ($offset < 0 || ($offset + $length) > $this->length) {
            return false;
        }

        // 将传入的值转换为 BigNumber 类型
        $value = new \chawuciren\BigNumber($value);
        $value = $value->toString();

        $buffer = pack($format, $value);
        $buffer = unpack('C*', $buffer);
        $buffer = array_slice($buffer, 0, count($buffer));

        // 格式化并存入 Buffer
        $bufferLength = count($buffer);
        for ($index = 0; $index < $length; $index++) {
            $uInt8Value = 0;

            if ($index < $bufferLength) {
                $uInt8Value = $buffer[$index];
            }

            $this->buffer[$offset + $index] = $uInt8Value;
        }

        // 更新偏移量
        $this->offset = $offset + $length;
        return $this->offset;
    }

    /**
     * @brief 读取一个无符号8位整型数据
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt8($offset = null)
    {
        return $this->readValue('C', $offset, 1);
    }

    /**
     * @brief 读取一个无符号16位整型数据(大端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt16BE($offset = null)
    {
        return $this->readValue('n', $offset, 2);
    }

    /**
     * @brief 读取一个无符号16位整型数据(小端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt16LE($offset = null)
    {
        return $this->readValue('v', $offset, 2);
    }

    /**
     * @brief 读取一个无符号32位整型数据(大端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt32BE($offset = null)
    {
        return $this->readValue('N', $offset, 4);
    }

    /**
     * @brief 读取一个无符号32位整型数据(小端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt32LE($offset = null)
    {
        return $this->readValue('V', $offset, 4);
    }

    /**
     * @brief 读取一个无符号64位整型数据(大端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt64BE($offset = null)
    {
        return $this->readValue('J', $offset, 8);
    }

    /**
     * @brief 读取一个无符号32位整型数据(小端序)
     *
     * @param $offset 偏移
     *
     * @return BigNumber
     */
    public function readUInt64LE($offset = null)
    {
        return $this->readValue('P', $offset, 8);
    }

    /**
     * @brief 写入一个无符号8位整型数据
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt8($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'C', $offset, 1);
    }

    /**
     * @brief 写入一个无符号16位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt16BE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'n', $offset, 2);
    }

    /**
     * @brief 写入一个无符号16位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt16LE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'v', $offset, 2);
    }

    /**
     * @brief 写入一个无符号32位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt32BE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'N', $offset, 4);
    }

    /**
     * @brief 写入一个无符号32位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt32LE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'V', $offset, 4);
    }

    /**
     * @brief 写入一个无符号64位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt64BE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'J', $offset, 8);
    }

    /**
     * @brief 写入一个无符号64位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeUInt64LE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'P', $offset, 8);
    }

    /**
     * @brief 获取一个 ByteBuffer 的切片
     *
     * @param $offset 起始位置
     * @param $length 长度
     *
     * @return  ByteBuffer
     */
    public function slice($offset = 0, $length = 0)
    {
        $bufferArray    = array_slice($this->buffer, $offset, $length);
        $bufferCount    = count($bufferArray);
        $buffer         = new self(null, $bufferCount);
        $buffer->buffer = $bufferArray;

        return $buffer;
    }
}
