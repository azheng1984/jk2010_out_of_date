<?php
namespace Hyperframework\Main; //$x = array();
//print_r(opcache_get_status("/home/az/quickquick/config/init.php"));
class _xx {
    public function hi() {
        echo 'hi';  
    }
}
function main() {
   echo 'xxx';
}
echo main();
//echo __NAMESPACE__;
exit;

//$s = microtime(true);
////$x = array();
//$x = array();
//for ($i = 0; $i < 1000000; ++$i) {
//    $x = [0 => $i];
//    //$x[0] = $i;
//    //strpos($name, '|');
//    //explode('|', $name);
//    //$name = str_replace(' | ', '|', $name);
//    //preg_match('/^[a-zA-Z0-9-|]+$/', $name);
//    //preg_match('/^([a-zA-Z0-9-|]|( \| ))+$/', $name);
//    //exit;
//    //preg_match('/^([a-zA-Z0-9-|]|( \| ))+$/', $name);
//}
//echo (microtime(true) - $s) * 1000;
//exit;

if (isset($_GET['b'])) {
echo file_get_contents('php://input');
    print_r($_GET);
    print_r($_POST);
    print_r($_FILES);
    echo $_SERVER['REQUEST_METHOD'];
    exit;
}

if (isset($_GET['r'])) {
    if ($_GET['r'] < 10) {
       header('http/1.1 302');
       header('Location: http://localhost/?r='. ($_GET['r'] + 1));
    }
    header('http1.1/:1');
    echo $_GET['r'];
    exit;
}

use Hyperframework\Web\Runner;
define('Hyperframework\Blog\ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . DIRECTORY_SEPARATOR . 'config'
    . DIRECTORY_SEPARATOR . 'init_const.php';
require HYPERFRAMEWORK_PATH . DIRECTORY_SEPARATOR . 'Web'
    . DIRECTORY_SEPARATOR . 'Runner.php';
Runner::run(__NAMESPACE__, ROOT_PATH);
//throw new \Exception
//trigger_error('xx', E_USER_ERROR);
?>
<form method="get" enctype="application/x-www-form-urlencoded" action="#sdf?q=s#2233">
<input type="checkbox" name ="hi" value="9"/>
<input type="checkbox" name ="hi" value="10"/>

<input type="submit" />
</form>
