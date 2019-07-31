<?php
namespace chawuciren;

use \chawuciren\BigNumber;

class ByteBuffer
{
    // 长度
    public $length;
    // 数组
    public $buffer;
    // 偏移
    public $offset;

    // 各种类型的数值长度定义
    const INT8_BYTE_LENGTH   = 1;
    const INT16_BYTE_LENGTH  = 2;
    const INT32_BYTE_LENGTH  = 4;
    const INT64_BYTE_LENGTH  = 8;
    const FLOAT_BYTE_LENGTH  = 4;
    const DOUBLE_BYTE_LENGTH = 8;

    /**
     * @brief 构造
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return
     */
    public function __construct($data = '', $length = null)
    {
        $this->length = 0;
        $this->buffer = [];
        $this->offset = 0;

        if ($length === null && (is_int($data) || is_float($data) || is_double($data) || is_long($data))) {
            $data   = self::alloc($data);
            $length = null;
        }

        $this->fill($data, 0, $length);
    }

    /**
     * @brief 构造一个新的 ByteBuffer 实例
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return ByteBuffer
     */
    public static function from($data = '', $length = null)
    {
        return new self($data, $length);
    }

    /**
     * @brief 初始化一个 ByteBuffer
     *
     * @param $length 长度
     * @param $fill 填充的UInt8值，默认为0
     *
     * @return ByteBuffer
     */
    public static function alloc($length = 0, $fill = 0)
    {
        $buffer = [];

        if ($fill < 0 || $fill > 255) {
            $fill = 0;
        }
        $buffer = array_pad($buffer, $length, $fill);
        $buffer = new self($buffer, $length);

        return $buffer;
    }

    /**
     * @brief 转换数字的大小端序
     *
     * @param $value 数字
     * @param $format 格式
     * @param $length 位数
     * @param $scale 精度
     *
     * @return BigNumber
     */
    public static function convertNumberByteOrder($value, $format = 'C', $length = self::INT8_BYTE_LENGTH, $scale = 0)
    {
        $buffer = self::alloc($length);
        $buffer->writeValue($value, $format, 0, $length);
        $values = $buffer->values();
        $values = array_reverse($values);
        $buffer = self::from($values);
        $value  = $buffer->readValue($format, 0, $length, $scale);

        return $value;
    }

    /**
     * @brief 判断数据是否ByteBuffer类型
     *
     * @return null
     */
    public static function isByteBuffer($data = null)
    {
        if (get_class($data) == get_class()) {
            return true;
        }

        return false;
    }

    /**
     * @brief 判断主机序是大端序还是小端序
     *
     * @return Bool
     */
    public static function isMachineBigEndian()
    {
        $bin = pack("L", 0x12345678);
        $hex = bin2hex($bin);
        if (ord(pack("H2", $hex)) === 0x78) {
            return false;
        }

        return true;
    }

    /**
     * @brief 设一个值到 ByteBuffer实例中
     *
     * @param $data String/BigNumber/ByteBuffer 要初始化的数据
     * @param $length Byte长度
     *
     * @return  ByteBuffer
     */
    public function fill($data = '', $offset = 0, $length = null)
    {
        // 初始化buffer
        if (empty($this->buffer)) {
            $this->buffer = [];
        }

        if (empty($offset) && $this->offset > 0) {
            $offset = $this->offset;
        }

        // 初始化数据和长度
        $buffer     = null;
        $dataLength = 0;

        // 解析传入的数据
        if (is_array($data)) {
            // 传入数组类型
            $dataLength = count($data);
            $buffer     = $data;
        } else if (is_object($data) && self::isByteBuffer($data)) {
            // 传入ByteBuffer类型
            $dataLength = $data->length;
            $buffer     = $data->buffer;
        } else {
            // 二进制数据
            $buffer     = unpack('C*', $data);
            $dataLength = count($buffer);
        }

        if (empty($length)) {
            $length = count($buffer);
        }
        $buffer = array_slice($buffer, 0, $length);

        // 重新获取长度
        if (empty($this->length) && $length > 0) {
            $this->length = $length;
        }
        //判断是否溢出
        if (($offset + $length) > $this->length) {
            return false;
        }

        // 将填充数据复制到缓冲区
        if ($length > count($buffer)) {
            $length = count($buffer);
        }
        for ($index = 0; $index < $length; $index++) {
            $this->buffer[$offset + $index] = $buffer[$index];
        }
        $this->offset = $offset + $length;

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
            $buffer .= pack('C', $this->buffer[$index]);
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
    public function readValue($format = 'C', $offset = 0, $length = self::INT8_BYTE_LENGTH, $scale = 0)
    {
        //初始化偏移量
        if ($offset === null) {
            $offset = $this->offset;
        }

        //偏移量超出则返回错误
        if ($offset < 0 || ($offset + $length) > $this->length) {
            return false;
        }

        $number = BigNumber::build('0', $scale);
        $buffer = '';
        for ($index = 0; $index < $length; $index++) {
            $buffer .= pack('C', $this->buffer[$offset + $index]);
        }

        $this->offset = $offset + $length;
        $value        = unpack($format, $buffer);
        if (!empty($value) && is_array($value) && count($value) == 1) {
            $value = $value[1];
            $value = $number->add($value);
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
    public function writeValue($value = 0, $format = 'C', $offset = 0, $length = self::INT8_BYTE_LENGTH)
    {
        if ($offset === null) {
            $offset = $this->offset;
        }

        if ($offset < 0 || ($offset + $length) > $this->length) {
            return false;
        }

        // 将传入的值转换为 BigNumber 类型
        $value = BigNumber::build($value);
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
     * @brief 返回缓冲区的数组值
     *
     * @return Array
     */
    public function values()
    {
        return $this->buffer;
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

    /**
     * @brief 读取一个有符号8位整型数据
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt8($offset = null, $scale = 0)
    {
        return $this->readValue('c', $offset, self::INT8_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个无符号8位整型数据
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt8($offset = null, $scale = 0)
    {
        return $this->readValue('C', $offset, self::INT8_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个有符号16位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt16BE($offset = null, $scale = 0)
    {
        $value = $this->readValue('s', $offset, self::INT16_BYTE_LENGTH, $scale);
        if (!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 's', self::INT16_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个有符号16位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt16LE($offset = null, $scale = 0)
    {
        $value = $this->readValue('s', $offset, self::INT16_BYTE_LENGTH, $scale);
        if (self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 's', self::INT16_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个无符号16位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt16BE($offset = null, $scale = 0)
    {
        return $this->readValue('n', $offset, self::INT16_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个无符号16位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt16LE($offset = null, $scale = 0)
    {
        return $this->readValue('v', $offset, self::INT16_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个有符号32位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt32BE($offset = null, $scale = 0)
    {
        $value = $this->readValue('l', $offset, self::INT32_BYTE_LENGTH, $scale);
        if (!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'l', self::INT32_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个有符号32位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt32LE($offset = null, $scale = 0)
    {
        $value = $this->readValue('l', $offset, self::INT32_BYTE_LENGTH, $scale);
        if (self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'l', self::INT32_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个无符号32位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt32BE($offset = null, $scale = 0)
    {
        return $this->readValue('N', $offset, self::INT32_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个无符号32位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt32LE($offset = null, $scale = 0)
    {
        return $this->readValue('V', $offset, self::INT32_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个有符号64位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt64BE($offset = null, $scale = 0)
    {
        $value = $this->readValue('q', $offset, self::INT64_BYTE_LENGTH, $scale);
        if (!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'q', self::INT64_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个有符号64位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readInt64LE($offset = null, $scale = 0)
    {
        $value = $this->readValue('q', $offset, self::INT64_BYTE_LENGTH, $scale);
        if (self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'q', self::INT64_BYTE_LENGTH, $scale);
        }

        return $value;
    }

    /**
     * @brief 读取一个无符号64位整型数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt64BE($offset = null, $scale = 0)
    {
        return $this->readValue('J', $offset, self::INT64_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个无符号32位整型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return BigNumber
     */
    public function readUInt64LE($offset = null, $scale = 0)
    {
        return $this->readValue('P', $offset, self::INT64_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个浮点数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return Int 偏移
     */
    public function readFloatBE($offset = 0, $scale = 0)
    {
        return $this->readValue('G', $offset, self::FLOAT_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个浮点型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return Int 偏移
     */
    public function readFloatLE($offset = 0, $scale = 0)
    {
        return $this->readValue('g', $offset, self::FLOAT_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个双精度浮点数据(大端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return Int 偏移
     */
    public function readDoubleBE($offset = 0, $scale = 0)
    {
        return $this->readValue('E', $offset, self::DOUBLE_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 读取一个双精度浮点型数据(小端序)
     *
     * @param $offset 偏移
     * @param $scale 读取的精度
     *
     * @return Int 偏移
     */
    public function readDoubleLE($offset = 0, $scale = 0)
    {
        return $this->readValue('e', $offset, self::DOUBLE_BYTE_LENGTH, $scale);
    }

    /**
     * @brief 写入一个有符号8位整型数据
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt8($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'c', $offset, self::INT8_BYTE_LENGTH);
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
        return $this->writeValue($value, 'C', $offset, self::INT8_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号16位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt16BE($value = 0, $offset = 0)
    {
        if(!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 's', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 's', $offset, self::INT16_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号16位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt16LE($value = 0, $offset = 0)
    {
        if(self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 's', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 's', $offset, self::INT16_BYTE_LENGTH);
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
        return $this->writeValue($value, 'n', $offset, self::INT16_BYTE_LENGTH);
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
        return $this->writeValue($value, 'v', $offset, self::INT16_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号32位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt32BE($value = 0, $offset = 0)
    {
        if(!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'l', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 'l', $offset, self::INT32_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号32位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt32LE($value = 0, $offset = 0)
    {
        if(self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'l', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 'l', $offset, self::INT32_BYTE_LENGTH);
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
        return $this->writeValue($value, 'N', $offset, self::INT32_BYTE_LENGTH);
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
        return $this->writeValue($value, 'V', $offset, self::INT32_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号64位整型数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt64BE($value = 0, $offset = 0)
    {
        if(!self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'q', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 'q', $offset, self::INT64_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个有符号64位整型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeInt64LE($value = 0, $offset = 0)
    {
        if(self::isMachineBigEndian()) {
            $value = self::convertNumberByteOrder($value, 'q', self::INT16_BYTE_LENGTH, $scale);
        }
        return $this->writeValue($value, 'q', $offset, self::INT64_BYTE_LENGTH);
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
        return $this->writeValue($value, 'J', $offset, self::INT64_BYTE_LENGTH);
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
        return $this->writeValue($value, 'P', $offset, self::INT64_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个浮点数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeFloatBE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'G', $offset, self::FLOAT_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个浮点型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeFloatLE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'g', $offset, self::FLOAT_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个双精度浮点数据(大端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeDoubleBE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'E', $offset, self::DOUBLE_BYTE_LENGTH);
    }

    /**
     * @brief 写入一个双精度浮点型数据(小端序)
     *
     * @param $value String/BigNumber/Int 要写入的数据
     * @param $offset 偏移
     *
     * @return Int 偏移
     */
    public function writeDoubleLE($value = 0, $offset = 0)
    {
        return $this->writeValue($value, 'e', $offset, self::DOUBLE_BYTE_LENGTH);
    }
}
