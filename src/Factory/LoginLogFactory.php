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
        if ($user) {
            $status = LoginLog::STATUS_WRONG_PASSWORD;
        } else {
            $status = LoginLog::STATUS_WRONG_USERNAME;
        }

        return $this->create($status, $request, $user);
    }

    public function goodLogin(Request $request, User $user): LoginLog
    {
        return $this->create(LoginLog::STATUS_OK, $request, $user);
    }

    public function create($status, Request $request, ?User $user = null): LoginLog
    {
        $loginLog = new LoginLog();
        $parser = new UserAgentParser();

        $remoteAddress = $request->getClientIp();
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        $parserData = $parser->parse($userAgent);

        $loginLog->setUser($user)
            ->setClient($user?->getClient())
            ->setUsername($request->request->get('username'))
            ->setPassword(!$user ? $request->request->get('password') : null)
            ->setIp(ip2long($remoteAddress))
            ->setServerDate(new \DateTime())
            ->setUserAgent($userAgent)
            ->setHost(gethostbyaddr($remoteAddress))
            ->setStatus($status)
            ->setBrowser($parserData->browser())
            ->setOs($parserData->platform())
        ;

        return $loginLog;
    }

}