# pickles2/px2-blog-kit

`px2-blog-kit` は、 Pickles 2 にブログ管理機能を追加します。

## 導入手順 - Setup

### 1. composer.json に pickles2/px2-blog-kit を追加

```bash
$ composer require pickles2/px2-blog-kit;
```

### 2. config.php に、プラグインを設定

設定ファイル `config.php` (通常は `./px-files/config.php`) を編集します。
`before_content` の先頭に設定を追加します。

```php
<?php

/* 中略 */

/**
 * funcs: Before content
 */
$conf->funcs->before_content = array(

    // BlogKit
    \pickles2\px2BlogKit\register::blog( array(
        "blogs" => array(
            "articles" => array( // ブログID
                "orderby" => "update_date", // 並べ替えに使用するカラム名
                "scending" => "desc", // 並び順 (昇順 asc or 降順 desc)
                "logical_path" => "/articles/{*}", // パンくず (サイトマップCSV上での記法と同じ)
            ),
        ),
    ) ),

);
```

### 3. ブログのリストを作成

`./px-files/blogs/` ディレクトリを作成し、ブログ記事一覧を配置します。

ブログ記事一覧は `${ブログID}.csv` の名前で作成します。
ブログIDを `articles` とした場合、 `articles.csv` になります。

CSVの記法は、サイトマップCSVと同じですが、 `id` 列、 `logical_path` 列 は含めないようにしてください。

次に示すのは、ブログCSVの記述例です。

```csv
"* title","* path","* release_date","* update_date","* article_summary","* article_keywords"
"サンプルブログページ3のタイトル","/articles/2023/03/18/samplepage_3/","2023-03-18","2023-03-18",,
"サンプルブログページ2のタイトル","/articles/2023/03/17/samplepage_2/","2023-03-17","2023-03-17",,
"サンプルブログページ1のタイトル","/articles/2023/03/16/samplepage_1/index.html","2023-03-16","2023-03-16",,
```

最新の `px2-sitemapexcel` プラグインを導入すると、 Excel 形式で編集できて便利です。


### 4. コンテンツを作成する

サイトマップに記載する通常のコンテンツと同様に、 `path` に設定したパスにコンテンツファイルを設置してください。


### 5. Blog Kit モジュールを使用して、記事一覧ページを作成する

Broccoli モジュール `Blog Kit` が同梱されています。
このモジュールには、ブログの一覧ページを作成するモジュールが含まれています。
これを使って、一覧ページを作成します。

#### 5-1. サイトマップCSVに一覧ページを追加する

一覧ページは、 プラグインオプションの `logical_path` に指定した親ページとなっているのが理想的です。

例えば `path` を `/articles/{*}` とします。
末尾についている `{*}` は、一覧ページのページネーションを処理するために必要です。

#### 5-2. 一覧ページのコンテンツに、記事一覧モジュールを配置する

配置したモジュールで、ブログID、ページあたりの記事件数、ページネーションのサイズ、並び順のキー、昇順/降順、リストページID を設定します。

ここで指定する ブログID は、 ブログCSVのファイル名の拡張子を含まない部分(例: `articles`) です。


### 6. RSSの出力設定

設定ファイル `config.php` (通常は `./px-files/config.php`) を編集します。
`before_output` の任意の位置に設定を追加します。

```php
<?php

/* 中略 */

/**
 * funcs: Before output
 */
$conf->funcs->before_output = array(

    // BlogKit: RSS出力
    \pickles2\px2BlogKit\register::feeds( array(
        "path_trigger" => "/",
        "blog_id" => "articles",
        "orderby" => "update_date",
        "scending" => "desc",
        'dpp' => 10,
        'lang' => 'ja',
        'scheme' => 'https',
        'domain' => 'yourdomain.com',
        'title' => 'test list 1',
        'description' => 'TEST LIST',
        'url_home' => 'https://yourdomain.com/',
        'url_index' => 'https://yourdomain.com/listsample/',
        'author' => 'Tomoya Koyanagi',
        'dist' => array(
            'atom-1.0' => '/rss/atom0100.xml',
            'rss-1.0' => '/rss/rss0100.rdf',
            'rss-2.0' => '/rss/rss0200.xml',
        ),
    ) ),

);
```


## 更新履歴 - Change log

### pickles2/px2-blog-kit v0.1.1 (リリース日未定)

- Broccoliモジュールに README を追加。
- プラグイン `\pickles2\px2BlogKit\register::feeds` を追加。

### pickles2/px2-blog-kit v0.1.0 (2023年4月22日)

- Initial Release


## ライセンス - License

MIT License https://opensource.org/licenses/mit-license.php


## 作者 - Author

- Tomoya Koyanagi <tomk79@gmail.com>
- website: <https://www.pxt.jp/>
- Twitter: @tomk79 <https://twitter.com/tomk79/>
