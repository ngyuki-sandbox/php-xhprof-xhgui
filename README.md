# [PHP]PHP 7.4 で xhprof/xhgui プロファイリング

だいぶ前に xhgui 使ったときはアプリ側にも xhgui のソースを入れて `xhgui/external/header.php` みたいなファイルを `auto_prepend_file` とかに設定していたと思うのですが、最新版だとだいぶ変わっていました。

## xhprof

とりあえず [tideways_xhprof 拡張](https://github.com/tideways/php-xhprof-extension) が必要です。Docker なら次のような感じでインストールできます。

```sh
RUN mkdir -p /tmp/tideways_xhprof &&\
    curl -fsSL https://github.com/tideways/php-xhprof-extension/archive/v5.0.2.tar.gz |\
        tar xzf - --strip-components=1 -C /tmp/tideways_xhprof &&\
    docker-php-ext-install /tmp/tideways_xhprof &&\
    rm -fr /tmp/tideways_xhprof
```

と思ったら [xhprof 拡張](https://pecl.php.net/package/xhprof)も PHP 7 対応でメンテ続いていたんですね。こっちでも良いかも。

```sh
RUN apk add --no-cache --virtual .build-deps autoconf gcc g++ make &&\
    pecl install xhprof &&\
    apk del .build-deps &&\
    rm -fr /tmp/pear &&\
    docker-php-ext-enable xhprof
```

## php-profiler

アプリ側には xhgui は必要無く、代わりに [perftools/php-profiler](https://packagist.org/packages/perftools/php-profiler) が必要です。プロファイル結果をアプリ側から MongoDB に直接保存しようとすると [xhgui-collector](https://packagist.org/packages/perftools/xhgui-collector) も必要です。

php-profiler の設定の `save.handler` で `SAVER_UPLOAD` を指定すればプロファイル結果を HTTP で xhgui へポストするようになるので楽ちんです。アプリ側に mongodb 拡張も必要ありません。

アプリケーションの index.php とか composer.json の autoload.files とかあるいは auto_prepend_file とかで次のようにプロファイラを開始します。

```php
$config = [
    'profiler.enable' => function () {
        return true;
    },

    // xhprof や tideways_xhprof で有効にするフラグ
    'profiler.flags' => [
        // 実行時間以外に収集するメトリクス
        // CPU や MEMORY も収集しないと xhgui で Notice エラーになるので実質必須
        \Xhgui\Profiler\ProfilingFlags::CPU,
        \Xhgui\Profiler\ProfilingFlags::MEMORY,

        // ビルトイン関数をプロファイル結果煮含めない
        \Xhgui\Profiler\ProfilingFlags::NO_BUILTINS,

        // xhprof や tideways_xhprof では無意味（tideways 拡張ではサポートされているらしい）
        \Xhgui\Profiler\ProfilingFlags::NO_SPANS,
    ],

    // プロファイル結果の保存ハンドラ
    'save.handler' => \Xhgui\Profiler\Profiler::SAVER_UPLOAD,

    // プロファイル結果のアップロード先
    'save.handler.upload' => [
        // xhgui の URL を指定する
        'uri' => 'http://xhgui/run/import',
    ],
];

$profiler = new \Xhgui\Profiler\Profiler($config);
$profiler->start();
```

`$profiler->start()` でプロファイルが開始されつつ、プロファイルを停止して結果を保存するためのシャットダウンハンドラが登録されます。

シャットダウン関数の中で `fastcgi_finish_request` でリクエストを終了させたうえで保存ハンドラを呼ぶため、保存に時間が掛かったとしてもページの表示が遅延することはありません。ただ、別の用途でシャットダウンハンドラを利用していてシャットダウンハンドラからレスポンスを返している場合、それが機能しなくなります。その場合、`$profiler->start(false)` のようにプロファイルを開始すればシャットダウン関数で `fastcgi_finish_request` は実行されなくなります。

## xhgui

xhgui は [edyan/xhgui](https://hub.docker.com/r/edyan/xhgui/) がオールインワンの Docker イメージなので楽です。docker-compose ならこれだけです。

```yaml
version: '3.7'
services:
  xhgui:
    image: edyan/xhgui
    ports:
      - '8142:80'
```

[xhgui/xhgui](https://hub.docker.com/r/xhgui/xhgui) というイメージもありますが、これは nginx や mongodb を別に用意する必要があります。あとなぜか `/var/www/xhgui/config` に謎のコンフィグファイルが置かれているため、別にコンフィルファイルを用意してマウントするか、あるいは削除しないと環境変数で設定を指定できません。。。

## さいごに
