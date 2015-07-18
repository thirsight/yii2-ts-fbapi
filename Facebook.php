<?php

namespace ts\fbapi;

require('facebook/autoload.php');

use Yii;
use yii\helpers\Url;
use yii\base\Exception;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRedirectLoginHelper;

/**
 * Facebook api
 */
class Facebook extends \yii\base\Object
{
    /**
     * @var string the application id, eg. 9166967xxxxxxxx
     */
    public $appId;
    /**
     * @var string the application secret, eg. 53ac4035a6183d50xxxxxxxxxxxxxxxx
     */
    public $appSecret;
    /**
     * @var string the URL Facebook should redirect users to after auth
     */
    public $redirectUrl;
    /**
     * 需要指定一个控制作为回调路径，参见 Url::toRoute()
     * @var array the route for create redirect url
     */
    public $redirectRoute = [];
    /**
     * @var string
     */
    public $redirectScheme = 'http';

    /**
     * Initializes Facebook api with application id and application secret.
     */
    public function init()
    {
        $this->redirectUrl = Url::toRoute($this->redirectRoute, $this->redirectScheme);
        FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
    }

    /**
     * 生成授权链接，获取用户公开的个人档案、邮箱（如果用户未验证邮箱，邮箱不会返回），允许贴文
     * @return string login url
     */
    public function getLoginUrl()
    {
        $helper = new FacebookRedirectLoginHelper($this->redirectUrl);
        return $helper->getLoginUrl(['email', 'user_posts', 'publish_actions']);
    }

    /**
     * 在所指定的控制器回调路径中，需要调用此函数
     * @return array|string
     */
    public function afterAuth()
    {
        $helper = new FacebookRedirectLoginHelper($this->redirectUrl);
        try {
            return $this->parseSession($helper->getSessionFromRedirect());
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 通过已存在的 access token 获取用户 Facebook 档案
     * @param string $accessToken
     * @return array|string
     */
    public function getUserByToken($accessToken)
    {
        return $this->parseSession(new FacebookSession($accessToken));
    }

    /**
     * FacebookSession data
     *
     * Facebook\FacebookSession::__set_state(array(
     *     'accessToken' =>
     *         Facebook\Entities\AccessToken::__set_state(array(
     *             'accessToken' => 'CAANBuxdCTQkBAI86GB6i1KabY4GKeBYHuPNBXv8AWnNdU5WgOD7oFLgZApBCJq4QYHA8t7VQZCwZABWgzyxC6OnqsbKpfqwkeWNsfM7rbL2dgjkYqWqTt9oOPVKvbyHZAJpNa7PQxvl3ZBxUehPpLUoZAumcBmYhkLBEfZBASc1etbLbxMUg7PtWQCuITAifIoQRZC64GdtRZBQZDZD',
     *             'machineId' => NULL,
     *             'expiresAt' =>
     *                 DateTime::__set_state(array(
     *                     'date' => '2015-08-12 10:42:39.000000',
     *                     'timezone_type' => 3,
     *                     'timezone' => 'Asia/Taipei',
     *                 )),
     *         )),
     *     'signedRequest' => NULL,
     * ));
     *
     * GraphUser data
     *
     * Facebook\GraphUser::__set_state(array(
     *     'backingData' =>
     *         array (
     *             'id' => '832555493495653',
     *             'first_name' => 'Haisen',
     *             'gender' => 'male',
     *             'last_name' => 'Zhang',
     *             'link' => 'https://www.facebook.com/app_scoped_user_id/832555493495653/',
     *             'locale' => 'zh_TW',
     *             'name' => 'Haisen Zhang',
     *             'timezone' => 8,
     *             'updated_time' => '2015-06-11T15:01:34+0000',
     *             'verified' => true,
     *         ),
     * ))
     *
     * @param FacebookSession $session
     * @return array|string
     */
    public function parseSession(FacebookSession $session)
    {
        try {
            /* 更新为60天授权 */
            $session = $session->getLongLivedSession();

            /* 获取个人档案 */
            $user = (new FacebookRequest($session, 'GET', '/me'))->execute()
                ->getGraphObject(GraphUser::className())
                ->asArray();

            /**
             * 个人相片
             * @see http://www.cnblogs.com/liminjun88/archive/2013/03/01/2938769.html
             */
            $user['picture'] = 'https://graph.facebook.com/' . $user['id'] . '/picture?width=80&height=80&return_ssl_resources=1';

            /* access token 及到期时间 */
            $user['access_token'] = $session->getToken();
            $user['expire_at'] = $session->getAccessToken()->getExpiresAt()->getTimestamp();

            return $user;
        } catch(\Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 在用户 Facebook 墙上贴文
     * @param string $accessToken
     * @param string $message
     * @param string|null $link
     * @return string
     */
    public function post($accessToken, $message, $link = null)
    {
        try {
            $post = (new FacebookRequest(
                new FacebookSession($accessToken),
                'POST',
                '/me/feed',
                array (
                    'message' => $message,
                    'link' => $link,
                )
            ))->execute()->getGraphObject()->asArray();

            return $post['id'];
        } catch(\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
