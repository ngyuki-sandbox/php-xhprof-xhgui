<?php
declare(strict_types=1);

namespace App;

require __DIR__ . '/../vendor/autoload.php';

$config = [
    'profiler.enable' => function () {
        return true;
    },
    'profiler.flags' => [
        \Xhgui\Profiler\ProfilingFlags::CPU,
        \Xhgui\Profiler\ProfilingFlags::MEMORY,
        \Xhgui\Profiler\ProfilingFlags::NO_BUILTINS,
        \Xhgui\Profiler\ProfilingFlags::NO_SPANS,
    ],
    'save.handler' => \Xhgui\Profiler\Profiler::SAVER_UPLOAD,
    'save.handler.upload' => [
        'uri' => 'http://xhgui/run/import',
    ],
];

$profiler = new \Xhgui\Profiler\Profiler($config);
$profiler->start();

Application::main();
