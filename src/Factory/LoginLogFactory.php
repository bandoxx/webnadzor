<?php

namespace App\Factory;

use App\Entity\LoginLog;
use App\Entity\User;
use donatj\UserAgent\UserAgentParser;
use Symfony\Component\HttpFoundation\Request;

class LoginLogFactory
{

    public function badLogin(Request $request, ?User $user = null): LoginLog
    {
        $loginLog = new LoginLog();
        $parser = new UserAgentParser();

        $remoteAddress = $request->server->get('REMOTE_ADDR');
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        $parserData = $parser->parse($userAgent);

        if ($user) {
            $loginLog->setUser($user);
            $loginLog->setClient($user->getClient());
            $loginLog->setUsername($user->getUsername());
            $loginLog->setStatus(LoginLog::STATUS_WRONG_PASSWORD);
        } else {
            $loginLog->setUsername($request->request->get('username'));
            $loginLog->setStatus(LoginLog::STATUS_WRONG_USERNAME);
        }

        $loginLog->setUser($user)
            ->setPassword($request->request->get('password'))
            ->setIp(ip2long($remoteAddress))
            ->setServerDate(new \DateTime())
            ->setUserAgent($userAgent)
            ->setHost(gethostbyaddr($remoteAddress))
            ->setBrowser($parserData->browser())
            ->setOs($parserData->platform())
        ;

        return $loginLog;
    }

    public function create(Request $request, User $user): LoginLog
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
            ->setStatus(LoginLog::STATUS_OK)
            ->setBrowser($parserData->browser())
            ->setOs($parserData->platform())
        ;

        return $loginLog;
    }

}