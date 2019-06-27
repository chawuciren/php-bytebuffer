### php-bytebuffer

![release](https://img.shields.io/badge/release-1.0.1-green.svg) ![php](https://img.shields.io/badge/php-%3E=5.3-green.svg) ![downloads](https://img.shields.io/badge/downloads-2.29k-green.svg)

## 关于

使用现代并且简约的方式来处理二进制数据

<br>
<br>

##  安装方式

开始安装：

#### 1.安装方式一，通过composer 安装

	composer require chawuciren/bignumber

#### 2.安装方式二，直接下载并 include

直接下载源码，引入 src/ByteBuffer.php

<br>
<br>

## 开始使用

初始化中传入的数值应使用字符串，譬如有一个取出数值并计算后返回给前端的接口，数据库中存储的类型为 decimal 时，应优先将取出的值初始化为 BigNumber，然后在代码中使用 BigNumber 进行计算，后在接口返回处使用：value() 方法获取字符串型的数值输出

#### 1.方式一：使用 new 语句

    use \chawuciren\ByteBuffer;

	$buffer = new ByteBuffer();

#### 2.方式二：使用静态方法 build

    use \chawuciren\ByteBuffer;
	$number = ByteBuffer::from('BufferString');

#### 3.方式三：使用 fill 方法赋值

	$number = new \chawuciren\BigNumber();
	$number->fill('0.002', 3);

<br>
<br>

## 方法列表

#### 1.valueOf

设置一个值到BigNumber实例中 

##### 参数:

| 参数名 | 类型 | 说明 |
|--|--|--|
| number | String/BigNumber | 字符串或BigNumber类型的数字 |
| scale| Int | 数字精度 |

##### 返回值: BigNumber(当前实例)

##### 示例:

	$number = new \chawuciren\BigNumber();
	$number->valueOf('0.002', 3);
	var_dump($number); //object(chawuciren\BigNumber)

<br>

