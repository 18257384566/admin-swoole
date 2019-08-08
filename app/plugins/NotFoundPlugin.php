<?php

namespace Shuffle\Frontend\Plugins;


use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;

class NotFoundPlugin extends Plugin
{

    /*
     * 这个函数将在每个controller、action执行之前执行
     */

    public function beforeException(Event $event, MvcDispatcher $dispatcher, DispatcherException $exception)
    {
        if ($exception instanceof DispatcherException) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $this->response->redirect('/index/show404');
                    return false;
            }
        }

        //错误提示页面
        $this->response->redirect('/index/show404');
        return false;

    }

}