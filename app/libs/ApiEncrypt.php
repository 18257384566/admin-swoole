<?php
namespace App\Libs;
/**
 * API接口数据加解密方法
 * Created by PhpStorm.
 * User: Echo
 * Date: 2019/2/27
 * Time: 16:02
 */

class ApiEncrypt{

    private $_cipher;// 加密方式 例如 "AES-256-CBC"
    private $_key;// 密钥 为了兼容JAVA、C 最好设置为16位
    private $_options = 0;// options 是以下标记的按位或： OPENSSL_RAW_DATA 、 OPENSSL_ZERO_PADDING
    private $_iv = '';// 非null的初始化向量 可以默认设置为 "0000000000000000"
    private $_tag = '';// 使用 AEAD 密码模式（GCM 或 CCM）时传引用的验证标签
    private $_aad = '';// 附加的验证数据
    private $_tagLength = 16;// 验证 tag 的长度。GCM 模式时，它的范围是 4 到 16

    public function __construct($cipher, $key, $options = 0, $iv = '', $tag = null, $add = '', $tagLength = 16){

        $this->_cipher = $cipher;
        $this->_options = $options;
//        $this->_tag = $tag;
//        $this->_aad = $add;
//        $this->_tagLength = $tagLength;
//        $ivlen = openssl_cipher_iv_length($cipher);// 获得该加密方式的iv长度
//        $this->_iv = openssl_random_pseudo_bytes($ivlen);// 生成相应长度的伪随机字节串作为初始化向量
        $this->_iv = $iv;// 生成相应长度的伪随机字节串作为初始化向量
        $this->_key = $key;
    }

    /**
     * 加密方法
     * @param $plaintext
     * @return string
     */
    public function encrypt($plaintext){

        $plaintext = $this->addPKCS7Padding($plaintext);
//        $ciphertext = openssl_encrypt($plaintext, $this->_cipher, $this->_key, $this->_options, $this->_iv, $this->_tag);
        $ciphertext = openssl_encrypt($plaintext, $this->_cipher, $this->_key, $this->_options, $this->_iv);
        return base64_encode($ciphertext);
    }

    /**
     * 解密方法
     * @param $ciphertext
     * @return string
     */
    public function decrypt($ciphertext){

        $ciphertext = base64_decode($ciphertext);
//        $original_plaintext = openssl_decrypt($ciphertext, $this->_cipher, $this->_key, $this->_options, $this->_iv, $this->_tag);
        $original_plaintext = openssl_decrypt($ciphertext, $this->_cipher, $this->_key, $this->_options, $this->_iv);
        $original_plaintext = $this->stripPKSC7Padding($original_plaintext);
        return $original_plaintext;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    private function addPKCS7Padding($source) {
        $source = trim($source);
        $block = openssl_cipher_iv_length('AES-256-CBC');
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    private function stripPKSC7Padding($source) {
        $char = substr($source, -1);
        $num = ord($char);
        $source = substr($source, 0, -$num);
        return $source;
    }
}