<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::where('email', 'digitex-admin@yopmail.com')->first();
if (!$user) {
    echo "user not found\n";
    exit(1);
}

function requestJson($kernel, $user, $method, $uri) {
    $request = \Illuminate\Http\Request::create($uri, $method);
    $request->setLaravelSession($app = app('session.store'));
    $request->session()->start();
    $request->setUserResolver(fn () => $user);
    \Illuminate\Support\Facades\Auth::login($user);
    $response = $kernel->handle($request);
    return $response;
}

$before = \App\Models\InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
echo "unread_before={$before}\n";

$feed = requestJson($kernel, $user, 'GET', '/notifications/feed');
echo "feed: {$feed->getStatusCode()} {$feed->getContent()}\n";

$n = \App\Models\InAppNotification::where('user_id', $user->id)->whereNull('read_at')->first();
if ($n) {
    $mark = requestJson($kernel, $user, 'POST', "/notifications/{$n->id}/read");
    echo "mark: {$mark->getStatusCode()} {$mark->getContent()}\n";
}

$mid = \App\Models\InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
echo "unread_mid={$mid}\n";

$all = requestJson($kernel, $user, 'POST', '/notifications/read-all');
echo "read_all: {$all->getStatusCode()} {$all->getContent()}\n";

$after = \App\Models\InAppNotification::where('user_id', $user->id)->whereNull('read_at')->count();
echo "unread_after={$after}\n";
