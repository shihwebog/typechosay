<?php

namespace TypechoPlugin\Say\Lib;

use Typecho\Plugin\Exception;
use DOMDocument;

/**
 * PHP7.1及其之上版本的回调加解密类库
 * 该版本依赖openssl_encrypt方法加解密，注意版本依赖 (PHP 5 >= 5.3.0, PHP 7)
 */
class WxWorkCallbackCrypto
{
  /**
   * @param token          钉钉开放平台上，开发者设置的token
   * @param encodingAESKey 钉钉开放台上，开发者设置的EncodingAESKey
   * @param corpId         企业自建应用-事件订阅, 使用appKey
   *                       企业自建应用-注册回调地址, 使用corpId
   *                       第三方企业应用, 使用suiteKey
   */

  private $m_sToken;
  private $m_sEncodingAesKey;
  private $m_sReceiveId;

  public function __construct($token, $encodingAESKey, $receiveId)
  {
    $this->m_sToken = $token;
    $this->m_sEncodingAesKey = $encodingAESKey;
    $this->m_sReceiveId = $receiveId;
  }
  
  public function verifyURL($sMsgSignature, $sTimeStamp, $sNonce, $sEchoStr)
  {
    if (strlen($this->m_sEncodingAesKey) != 43) {
      return ErrorCode::$IllegalAesKey;
    }

    $pc = new Prpcrypt($this->m_sEncodingAesKey);
    $sha1 = new SHA1;
    $array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $sEchoStr);
    $ret = $array[0];

    if ($ret != 0) {
      return $ret;
    }

    $signature = $array[1];
    if ($signature != $sMsgSignature) {
      return ErrorCode::$ValidateSignatureError;
    }

    $result = $pc->decrypt($sEchoStr, $this->m_sReceiveId);
    if ($result[0] != 0) {
      return $result[0];
    }
    return $result[1];
  }

  public function encryptMsg($sReplyMsg, $sTimeStamp, $sNonce)
	{
		$pc = new Prpcrypt($this->m_sEncodingAesKey);

		//加密
		$array = $pc->encrypt($sReplyMsg, $this->m_sReceiveId);
		$ret = $array[0];
		if ($ret != 0) {
			return $ret;
		}

		if ($sTimeStamp == null) {
			$sTimeStamp = time();
		}
		$encrypt = $array[1];

		//生成安全签名
		$sha1 = new SHA1;
		$array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $encrypt);
		$ret = $array[0];
		if ($ret != 0) {
			return $ret;
		}
		$signature = $array[1];

		//生成发送的xml
		$xmlparse = new XMLParse;
		return $xmlparse->generate($encrypt, $signature, $sTimeStamp, $sNonce);
	}

  public function decryptMsg($sMsgSignature, $sTimeStamp = null, $sNonce, $sPostData)
	{
		if (strlen($this->m_sEncodingAesKey) != 43) {
			return ErrorCode::$IllegalAesKey;
		}

		$pc = new Prpcrypt($this->m_sEncodingAesKey);

		//提取密文
		$xmlparse = new XMLParse;
		$array = $xmlparse->extract($sPostData);
		$ret = $array[0];

		if ($ret != 0) {
			return $ret;
		}

		if ($sTimeStamp == null) {
			$sTimeStamp = time();
		}

		$encrypt = $array[1];

		//验证安全签名
		$sha1 = new SHA1;
		$array = $sha1->getSHA1($this->m_sToken, $sTimeStamp, $sNonce, $encrypt);
		$ret = $array[0];

		if ($ret != 0) {
			return $ret;
		}

		$signature = $array[1];
		if ($signature != $sMsgSignature) {
			return ErrorCode::$ValidateSignatureError;
		}

		$result = $pc->decrypt($encrypt, $this->m_sReceiveId);
		if ($result[0] != 0) {
			return $result[0];
		}
		return $result[1];
	}
}

class SHA1
{
	public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
	{
		//排序
		try {
			$array = array($encrypt_msg, $token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			$str = implode($array);
			return array(ErrorCode::$OK, sha1($str));
		} catch (Exception $e) {
			print $e . "\n";
			return array(ErrorCode::$ComputeSignatureError, null);
		}
	}

}

/**
 * error code 说明.
 * <ul>
 *    <li>-900004: encodingAESKey 非法</li>
 *    <li>-900005: 签名验证错误</li>
 *    <li>-900006: sha加密生成签名失败</li>
 *    <li>-900007: aes 加密失败</li>
 *    <li>-900008: aes 解密失败</li>
 *    <li>-900010: suiteKey 校验错误</li>
 * </ul>
 */
class ErrorCode
{
	public static $OK = 0;
	
	public static $IllegalAesKey = '900004:encodingAESKey 非法';
	public static $ValidateSignatureError = '900005:签名验证错误';
	public static $ComputeSignatureError = '900006:SHA1加密生成签名失败';
	public static $EncryptAESError = '900007:Aes 加密失败';
	public static $DecryptAESError = '900008:Aes 解密失败';
	public static $ValidateSuiteKeyError = '900010:SuiteKey 校验错误';
}

class PKCS7Encoder
{
  public static $block_size = 32;

  function encode($text)
  {
    $block_size = PKCS7Encoder::$block_size;
    $text_length = strlen($text);
    $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
    if ($amount_to_pad == 0) {
      $amount_to_pad = PKCS7Encoder::block_size;
    }
    $pad_chr = chr($amount_to_pad);
    $tmp = "";
    for ($index = 0; $index < $amount_to_pad; $index++) {
      $tmp .= $pad_chr;
    }
    return $text . $tmp;
  }

  function decode($text)
  {
    $pad = ord(substr($text, -1));
    if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
      $pad = 0;
    }
    return substr($text, 0, (strlen($text) - $pad));
  }
}


class Prpcrypt
{
  public $key = null;
  public $iv = null;

  public function __construct($k)
  {
    $this->key = base64_decode($k . '=');
    $this->iv = substr($this->key, 0, 16);
  }

  public function encrypt($text, $receiveId)
  {
    try {
        //拼接
        $text = $this->getRandomStr() . pack('N', strlen($text)) . $text . $receiveId;
        //添加PKCS#7填充
        $pkc_encoder = new PKCS7Encoder;
        $text = $pkc_encoder->encode($text);
        //加密
        if (function_exists('openssl_encrypt')) {
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
        } else {
            $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, base64_decode($text), MCRYPT_MODE_CBC, $this->iv);
        }
        return array(ErrorCode::$OK, $encrypted);
    } catch (Exception $e) {
        print $e;
        return array(MyErrorCode::$EncryptAESError, null);
    }
  }

    /**
     * 解密
     *
     * @param $encrypted
     * @param $receiveId
     * @return array
     */
    public function decrypt($encrypted, $receiveId)
    {
        try {
            //解密
            if (function_exists('openssl_decrypt')) {
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            } else {
                $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, base64_decode($encrypted), MCRYPT_MODE_CBC, $this->iv);
            }
        } catch (Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }
        try {
            //删除PKCS#7填充
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
            if (strlen($result) < 16) {
                return array();
            }
            //拆分
            $content = substr($result, 16, strlen($result));
            $len_list = unpack('N', substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_receiveId = substr($content, $xml_len + 4);
        } catch (Exception $e) {
            print $e;
            return array(ErrorCode::$IllegalBuffer, null);
        }
        if ($from_receiveId != $receiveId) {
            return array(ErrorCode::$ValidateCorpidError, null);
        }
        return array(0, $xml_content);
    }

    /**
     * 生成随机字符串
     *
     * @return string
     */
    private function getRandomStr()
    {
        $str = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}

class XMLParse
{

	/**
	 * 提取出xml数据包中的加密消息
	 * @param string $xmltext 待提取的xml字符串
	 * @return string 提取出的加密消息字符串
	 */
	public function extract($xmltext)
	{
		try {
			$xml = new DOMDocument();
			$xml->loadXML($xmltext);
			$array_e = $xml->getElementsByTagName('Encrypt');
			$encrypt = $array_e->item(0)->nodeValue;
			return array(0, $encrypt);
		} catch (Exception $e) {
			print $e . "\n";
			return array(ErrorCode::$ParseXmlError, null);
		}
	}

	/**
	 * 生成xml消息
	 * @param string $encrypt 加密后的消息密文
	 * @param string $signature 安全签名
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 */
	public function generate($encrypt, $signature, $timestamp, $nonce)
	{
		$format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
		return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
	}

}
?>