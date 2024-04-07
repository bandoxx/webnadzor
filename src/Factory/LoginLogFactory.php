<?php

namespace App\Factory;

use App\Entity\LoginLog;
use App\Entity\User;
use donatj\UserAgent\UserAgentParser;
use Symfony\Component\HttpFoundation\Request;

class LoginLogFactory
{
    public function create(Request $request, User $user, bool $successLogin = true): LoginLog
    {
        $loginLog = new LoginLog();
        $parser = new UserAgentParser();

        $remoteAddress = $request->server->get('REMOTE_ADDR');
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        $parserData = $parser->parse($userAgent);

        $loginLog->setUser($user)
            ->setClient($user->getClient())
            ->setUsername($request->request->get('username'))
            ->setPassword($request->request->get('password'))
            ->setIp(ip2long($remoteAddress))
            ->setServerDate(new \DateTime())
            ->setUserAgent($userAgent)
            ->setHost(gethostbyaddr($remoteAddress))
            ->setStatus(1)
            ->setBrowser($parserData->browser())
            ->setOs($parserData->platform())
        ;

        return $loginLog;
    }

}