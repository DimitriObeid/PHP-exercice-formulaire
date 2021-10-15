<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Index</title>
    </head>

    <body>
        <?php
            include_once("form.class.php");

            $request_method = strtoupper($_SERVER['REQUEST_METHOD']);
            $class = Article::class;
            if ($request_method === 'GET') {
                echo Article::MakeForm($class);
            } elseif ($request_method === 'POST') {
                $article = Article::MakeClassFromArray($class, $_POST[$class]);
                print_r($article);
            }
        ?>
    </body>
</html>
