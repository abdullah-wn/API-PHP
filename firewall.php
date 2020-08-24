<?php

namespace App;

class Firewall {
    public static function middleware ($req, $res, $next) {
        /*
        $role = null;
        
        if ($req->session && 'user' in $req->session) role = req.session.user.role;
        else $role = 'ROLE_ANONYMOUS';
        */
    
        $allow = false;
        switch ($role) {
            case 'ROLE_ADMIN':
                $allow = $adminEndpoints.some(verifyRight($req));
                echo 'ROLE_ADMIN :' + $allow;
                if ($allow) {
                    $next();
                    return;
                }
            case 'ROLE_USER':
                $allow = $userEndpoints.some(verifyRight($req));
                echo 'ROLE_USER :' + $allow;
                if ($allow) {
                    $next();
                    return;
                }
            case 'ROLE_ANONYMOUS':
                $allow = $anonymousEndpoints.some(verifyRight($req));
                echo 'ROLE_ANONYMOUS :' + $allow;
                if ($allow) {
                    $next();
                    return;
                }
            default:
                $res->status(403);
                $res->send('Unauthorized request');
                return;
        }
    }
    

static function verifyRight($req){ 
    return function ($endpoint) {
        $test = match($endpoint->path);
        $allow = test($req->path) && $endpoint->method === $req->method;
        
        if(isset($endpoint->callbackCheck))
            return $endpoint->callbackCheck($req, $allow);
        return  $allow;
    }
};

const anonymousEndpoints = [
    [
        'method' => Method.POST,
        'path' => '/upload-media', //TODO move to admin
    ],
    // Project
    [
        'method' => Method.DELETE,
        'path' => '/delete-project/:id', //TODO move to admin
    ],
    [
        'method' => Method.POST,
        'path' => '/project', //TODO move to admin
    ],
    [
        'method' => Method.PUT,
        'path' => '/project', //TODO move to admin
    ],
    [
        'method' => Method.GET,
        'path' => '/project',
    ],
    [
        'method' => Method.GET,
        'path' => '/project/:id',
    ],
    // Question
    [
        'method' => Method.GET,
        'path' => '/question',
    ],
    [
        'method' => Method.GET,
        'path' => '/question/:id',
    ],
    // Survey
    [
        'method' => Method.GET,
        'path' => '/survey',
    ],
    [
        'method' => Method.GET,
        'path' => '/survey/:id',
    ],
    [
        'method' => Method.PUT,
        'path' => '/survey', //TODO move to admin
    ],
    [
        'method' => Method.POST,
        'path' => '/submit-survey',
    ],
    // Commentary
    [
        'method' => Method.GET,
        'path' => '/commentary',
    ],
    [
        'method' => Method.GET,
        'path' => '/commentary/:id',
    ],
    // Media
    [
        'method' => Method.GET,
        'path' => '/media/:id',
    ],
    [
        'method' => Method.GET,
        'path' => '/media',
    ],
    [
        'method' => Method.POST,
        'path' => '/media', //TODO move to admin
    ],
    [
        'method' => Method.POST,
        'path' => '/user',
    ],
    [
        'method' => Method.POST,
        'path' => '/login',
    ],
    [
        'method' => Method.GET,
        'path' => '/logout',
    ],
];

const userEndpoints = [
    // User
    [
        'method' => Method.GET,
        'path' => '/user/:id'
    ],
    [
        'method' => Method.PUT,
        'path' => '/user'
    ],
    [
        'method' => Method.DELETE,
        'path' => '/user/:id'
    ],
    // Agenda
    [
        'method' => Method.GET,
        'path' => '/agenda',
    ],
    [
        'method' => Method.GET,
        'path' => '/agenda/:id',
    ],
    // Commentary
    [
        'method' => Method.POST,
        'path' => '/commentary',
    ],
    [
        'method' => Method.PUT,
        'path' => '/commentary'
    ],
    [
        'method' => Method.DELETE,
        'path' => '/commentary/:id'
    ],
    // Donations
    [
        'method' => Method.GET,
        'path' => '/donationpayment/:id'
    ],
    [
        'method' => Method.GET,
        'path' => '/donationphysical/:id'
    ],
];

const adminEndpoints = [
    // User
    [
        'method' => Method.GET,
        'path' => '/user',
    ],
    [
        'method' => Method.GET,
        'path' => '/user/:id',
    ],
    // Project
    [
        'method' => Method.POST,
        'path' => '/project',
    ],
    [
        'method' => Method.DELETE,
        'path' => '/project/:id',
    ],
    [
        'method' => Method.PUT,
        'path' => '/project',
    ],
    // Survey
    [
        'method' => Method.POST,
        'path' => '/survey',
    ],
    [
        'method' => Method.DELETE,
        'path' => '/survey/:id',
    ],
    [
        'method' => Method.PUT,
        'path' => '/survey',
    ],
    // Donations
    [
        'method' => Method.PUT,
        'path' => '/donationpayment/:id',
    ],
    [
        'method' => Method.DELETE,
        'path' => '/donationpayment/:id',
    ],
    [
        'method' => Method.PUT,
        'path' => '/donationphysical/:id',
    ],
    [
        'method' => Method.DELETE,
        'path' => '/donationphysical/:id',
    ],
    // Media
    [
        'method' => Method.POST,
        'path' => '/media',
    ],
    [
        'method' => Method.PUT,
        'path' => '/media/:id',
    ],
    [
        'method' => Method.DELETE,
        'path' => '/media/:id',
    ],
];

}
