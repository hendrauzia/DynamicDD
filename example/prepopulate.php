<?php
require_once "../DynamicDD.php";

if (!empty($_POST)){
  echo "<pre>";
  print_r($_POST);
  echo "</pre>";
}
$dd = new DynamicDD();
?>

<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>DROPDOWN SINGLE</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
</head>
<body>
<div>
    <p>Dropdown Demo - Single</p>
    <form method="post">
        <fieldset>
            <?= $dd->generateDD("A") ?>
            <input type="submit" value="Send" name="submit" />
        </fieldset>
        <?= $dd->generateJS() ?>
    </form>
</div>
</body>
</html>
