<?php
declare(strict_types=1);

namespace App\helpers;

$profiler = new \Xhgui\Profiler\Profiler([
    'profiler.enable' => function () {
        return true;
    },

    'profiler.flags' => [
        // @see https://github.com/perftools/xhgui/issues/146
        \Xhgui\Profiler\ProfilingFlags::CPU,
        \Xhgui\Profiler\ProfilingFlags::MEMORY,
        \Xhgui\Profiler\ProfilingFlags::NO_BUILTINS,
        \Xhgui\Profiler\ProfilingFlags::NO_SPANS,
    ],

    'save.handler' => \Xhgui\Profiler\Profiler::SAVER_UPLOAD,

    'save.handler.upload' => [
        'uri' => 'http://xhgui/run/import',
    ],
]);

$profiler->enable();

register_shutdown_function(function () use ($profiler) {
    ignore_user_abort(true);
    session_write_close();
    flush();
    fastcgi_finish_request();

    // @see https://github.com/perftools/xhgui/issues/209
    // アップストリームの 0.16.0 では修正済なのでこのハックは不要
    // しかし edyan/xhgui だとまだ 0.14.0 なので残しておく
    $data = $profiler->disable();
    $profile = [];
    foreach($data['profile'] as $key => $value) {
        $profile[strtr($key, ['.' => '_'])] = $value;
    }
    $data['profile'] = $profile;
    $profiler->save($data);
});
